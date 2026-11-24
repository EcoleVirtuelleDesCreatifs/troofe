<?php

namespace App\Services;

use App\Models\Event;
use App\Models\MenuItem;
use App\Models\Order;
use App\Models\Restaurant;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Notifications\Commerce\NewOrderReceived;
use App\Notifications\Commerce\NewTicketSale;
use App\Notifications\Commerce\OrderPlaced;
use App\Notifications\Commerce\OrderStatusChanged;
use App\Notifications\Commerce\TicketsIssued;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrderService
{
    /** @param array<int,int> $quantities menu_item_id => quantité */
    public function createFoodOrder(User $user, Restaurant $restaurant, array $quantities, array $data): Order
    {
        $items = MenuItem::where('restaurant_id', $restaurant->id)->where('is_available', true)
            ->whereIn('id', array_keys(array_filter($quantities)))->get();

        if ($items->isEmpty()) {
            throw ValidationException::withMessages(['items' => __('Choisissez au moins un plat.')]);
        }

        return DB::transaction(function () use ($user, $restaurant, $quantities, $data, $items) {
            $fees = $data['fulfilment'] === 'livraison' ? (int) $restaurant->delivery_fee : 0;
            $order = Order::create([
                'type' => 'repas', 'user_id' => $user->id, 'restaurant_id' => $restaurant->id,
                'fulfilment' => $data['fulfilment'], 'delivery_address' => $data['delivery_address'] ?? null,
                'contact_phone' => $data['contact_phone'], 'note' => $data['note'] ?? null, 'fees' => $fees,
            ]);
            $subtotal = 0;
            foreach ($items as $item) {
                $qty = min(50, max(1, (int) $quantities[$item->id]));
                $order->items()->create(['item_type' => $item->getMorphClass(), 'item_id' => $item->id, 'name' => $item->name, 'unit_price' => $item->price, 'quantity' => $qty, 'total' => $item->price * $qty]);
                $subtotal += $item->price * $qty;
            }
            $rate = $restaurant->owner?->currentPlan()->commissionRate() ?? config('troofe.commission_rate');
            $order->update(['subtotal' => $subtotal, 'total' => $subtotal + $fees, 'commission' => (int) round($subtotal * $rate / 100)]);

            return $order;
        });
    }

    /**
     * @param array<int,int> $quantities ticket_type_id => quantité
     * @param array<int,string> $holders noms des porteurs (facultatif)
     */
    public function createTicketOrder(User $user, Event $event, array $quantities, array $holders, string $phone): Order
    {
        return DB::transaction(function () use ($user, $event, $quantities, $holders, $phone) {
            $types = TicketType::where('event_id', $event->id)->whereIn('id', array_keys(array_filter($quantities)))->lockForUpdate()->get();
            if ($types->isEmpty()) {
                throw ValidationException::withMessages(['tickets' => __('Choisissez au moins un billet.')]);
            }
            $order = Order::create(['type' => 'billets', 'user_id' => $user->id, 'event_id' => $event->id, 'contact_phone' => $phone, 'meta' => ['holders' => array_values(array_filter(array_map('trim', $holders)))]]);
            $subtotal = 0;
            foreach ($types as $type) {
                $qty = (int) $quantities[$type->id];
                if ($qty > $type->max_per_order) {
                    throw ValidationException::withMessages(['tickets' => __('Maximum :n billets « :name » par commande.', ['n' => $type->max_per_order, 'name' => $type->name])]);
                }
                if ($qty > $type->remaining()) {
                    throw ValidationException::withMessages(['tickets' => __('Il ne reste que :n billet(s) « :name ».', ['n' => $type->remaining(), 'name' => $type->name])]);
                }
                $type->increment('sold', $qty); // réservation du stock
                $order->items()->create(['item_type' => $type->getMorphClass(), 'item_id' => $type->id, 'name' => $type->name, 'unit_price' => $type->price, 'quantity' => $qty, 'total' => $type->price * $qty]);
                $subtotal += $type->price * $qty;
            }
            $rate = $event->organizer?->currentPlan()->commissionRate() ?? config('troofe.commission_rate');
            $order->forceFill(['subtotal' => $subtotal, 'total' => $subtotal, 'commission' => (int) round($subtotal * $rate / 100)])->save();

            return $order;
        });
    }

    /** Après paiement : billets émis ou commande transmise au restaurant */
    public function fulfil(Order $order): void
    {
        if ($order->status !== 'en_attente') {
            return;
        }
        $order->update(['status' => 'payee', 'paid_at' => now()]);

        if ($order->type === 'billets') {
            $holders = $order->meta['holders'] ?? [];
            $n = 0;
            foreach ($order->items as $item) {
                for ($i = 0; $i < $item->quantity; $i++) {
                    Ticket::create(['order_id' => $order->id, 'ticket_type_id' => $item->item_id, 'user_id' => $order->user_id, 'code' => (string) Str::uuid(), 'holder_name' => $holders[$n++] ?? $order->user->name]);
                }
            }
            $order->update(['status' => 'terminee']);
            DB::afterCommit(function () use ($order) {
                $order->load('tickets.type', 'event.organizer');
                $order->user->notify(new TicketsIssued($order));
                $order->event->organizer?->notify(new NewTicketSale($order));
            });

            return;
        }

        DB::afterCommit(function () use ($order) {
            $order->load('items', 'restaurant.owner', 'user');
            $order->user->notify(new OrderPlaced($order));
            $order->restaurant->owner?->notify(new NewOrderReceived($order));
        });
    }

    /** Étape suivante côté restaurateur (ou annulation) */
    public function advance(Order $order, string $status): void
    {
        $current = $order->fresh();
        $allowed = $current && $current->type === 'repas' && (
            (Order::KITCHEN_FLOW[$current->status] ?? null) === $status
            || ($current->status === 'payee' && $status === 'annulee')
            || ($current->status === 'prete' && $current->fulfilment === 'livraison' && $status === 'livree')
        );
        abort_unless($allowed, 422);
        $updated = Order::whereKey($current->id)->where('status', $current->status)->update(['status' => $status]);
        abort_unless($updated === 1, 409);
        $order->refresh();
        $order->user->notify(new OrderStatusChanged($order));
    }

    /** Libère les billets réservés d'une commande non payée */
    public function releaseStock(Order $order): void
    {
        if ($order->type !== 'billets' || $order->status !== 'en_attente') {
            return;
        }
        DB::transaction(function () use ($order) {
            foreach ($order->items as $item) {
                TicketType::whereKey($item->item_id)->where('sold', '>=', $item->quantity)->decrement('sold', $item->quantity);
            }
            $order->update(['status' => 'annulee']);
        });
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Restaurant;
use App\Models\Ticket;
use App\Services\OrderService;
use App\Services\PaymentService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    public function __construct(private OrderService $orders, private PaymentService $payments) {}

    public function storeFood(Request $request, Restaurant $restaurant)
    {
        abort_unless($restaurant->isPublished() && $restaurant->accepts_orders, 404);
        $data = $request->validate([
            'items' => ['required', 'array'],
            'items.*' => ['integer', 'min:0', 'max:50'],
            'fulfilment' => ['required', Rule::in($restaurant->delivery ? ['retrait', 'livraison'] : ['retrait'])],
            'delivery_address' => ['required_if:fulfilment,livraison', 'nullable', 'string', 'max:255'],
            'contact_phone' => ['required', 'string', 'max:40'],
            'note' => ['nullable', 'string', 'max:500'],
        ]);
        $order = $this->orders->createFoodOrder($request->user(), $restaurant, $data['items'], $data);

        return redirect()->away($this->payments->start($request->user(), $order, $order->total, __('Commande :ref — :place', ['ref' => $order->reference, 'place' => $restaurant->name])));
    }

    public function storeTickets(Request $request, Event $event)
    {
        abort_unless($event->isPublished() && $event->starts_at->isFuture(), 404);
        $data = $request->validate([
            'tickets' => ['required', 'array'],
            'tickets.*' => ['integer', 'min:0', 'max:20'],
            'holders' => ['nullable', 'array'],
            'holders.*' => ['nullable', 'string', 'max:80'],
            'contact_phone' => ['required', 'string', 'max:40'],
        ]);
        $order = $this->orders->createTicketOrder($request->user(), $event, $data['tickets'], $data['holders'] ?? [], $data['contact_phone']);

        return redirect()->away($this->payments->start($request->user(), $order, $order->total, __('Billets :ref — :event', ['ref' => $order->reference, 'event' => $event->title])));
    }

    public function ticket(Request $request, string $code)
    {
        $ticket = Ticket::where('code', $code)->with('type.event', 'order')->firstOrFail();
        abort_unless($ticket->user_id === $request->user()->id || $request->user()->isAdmin(), 403);

        return view('pages.ticket', compact('ticket'));
    }

    public function checkinForm()
    {
        return view('pages.checkin');
    }

    /** Contrôle d'accès : un billet ne peut être validé qu'une fois */
    public function checkin(Request $request)
    {
        $code = trim((string) $request->validate(['code' => 'required|string|max:64'])['code']);
        // Code complet (QR) ou 8 premiers caractères imprimés sur le billet
        $ticket = mb_strlen($code) >= 8 ? Ticket::with('type.event')->where('code', $code)->orWhere('code', 'like', strtolower($code).'%')->first() : null;
        $user = $request->user();

        $result = match (true) {
            ! $ticket => ['bad', __('Billet inconnu.')],
            ! $user->isAdmin() && $ticket->type->event->organizer_id !== $user->id => ['bad', __('Ce billet concerne un événement que vous n\'organisez pas.')],
            (bool) $ticket->checked_in_at => ['warn', __('Déjà utilisé le :date.', ['date' => date_human($ticket->checked_in_at, 'D MMM, HH:mm')])],
            default => null,
        };
        if (! $result) {
            $ticket->update(['checked_in_at' => now(), 'checked_in_by' => $user->id]);
            $result = ['ok', __('Entrée validée : :name (:type).', ['name' => $ticket->holder_name, 'type' => $ticket->type->name])];
        }

        return back()->with('checkin', $result)->withInput();
    }
}

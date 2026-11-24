<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Payment;
use App\Models\Restaurant;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Notifications\Billing\PaymentFailed;
use App\Services\OrderService;
use App\Services\PaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Tests\TestCase;

class BusinessWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    private function order(string $status = 'en_attente'): Order
    {
        $buyer = User::factory()->create();
        $restaurant = Restaurant::create(['owner_id' => User::factory()->create()->id, 'name' => 'Restaurant Test', 'cuisine' => 'Ivoirienne', 'city' => 'Abidjan', 'country' => 'CI']);

        return Order::create(['user_id' => $buyer->id, 'restaurant_id' => $restaurant->id, 'type' => 'repas', 'status' => $status, 'fulfilment' => 'retrait', 'subtotal' => 5000, 'total' => 5000]);
    }

    private function payment(Order $order): Payment
    {
        return Payment::create(['reference' => 'TEST-'.Str::uuid(), 'user_id' => $order->user_id, 'payable_type' => 'order', 'payable_id' => $order->id, 'description' => 'Test', 'amount' => $order->total, 'currency' => 'XOF', 'provider' => 'simulation', 'status' => 'pending']);
    }

    public function test_paid_payment_cannot_be_overwritten_by_a_stale_failure(): void
    {
        $order = $this->order();
        $payment = $this->payment($order);
        $stale = clone $payment;
        app(PaymentService::class)->markPaid($payment, 'TEST', []);
        app(PaymentService::class)->markFailed($stale, ['status' => 'failed']);
        $this->assertSame('paid', $payment->fresh()->status);
        $this->assertSame('payee', $order->fresh()->status);
        Notification::assertNotSentTo($order->user, PaymentFailed::class);
    }

    public function test_repeated_failure_sends_only_one_failure_notification(): void
    {
        $order = $this->order();
        $payment = $this->payment($order);
        app(PaymentService::class)->markFailed($payment, []);
        app(PaymentService::class)->markFailed($payment, []);
        Notification::assertSentToTimes($order->user, PaymentFailed::class, 1);
    }

    public function test_orders_cannot_skip_steps_or_reopen_after_cancellation(): void
    {
        foreach (['payee' => 'prete', 'annulee' => 'confirmee', 'terminee' => 'en_preparation'] as $before => $after) {
            $order = $this->order($before);
            try {
                app(OrderService::class)->advance($order, $after);
                $this->fail('Invalid order transition was accepted.');
            } catch (\Symfony\Component\HttpKernel\Exception\HttpException $exception) {
                $this->assertSame(422, $exception->getStatusCode());
            }
            $this->assertSame($before, $order->fresh()->status);
        }
    }

    public function test_valid_kitchen_workflow_still_works(): void
    {
        $order = $this->order('payee');
        foreach (['confirmee', 'en_preparation', 'prete', 'terminee'] as $status) {
            app(OrderService::class)->advance($order, $status);
            $this->assertSame($status, $order->fresh()->status);
        }
    }

    public function test_wildcards_cannot_be_used_as_ticket_codes(): void
    {
        $this->seed();
        $type = TicketType::with('event.organizer')->firstOrFail();
        $order = Order::create(['user_id' => User::factory()->create()->id, 'event_id' => $type->event_id, 'type' => 'billets', 'status' => 'terminee']);
        $ticket = Ticket::create(['user_id' => $order->user_id, 'order_id' => $order->id, 'ticket_type_id' => $type->id, 'code' => (string) Str::uuid(), 'holder_name' => 'Test']);
        $this->actingAs($type->event->organizer)->post('/controle-acces', ['code' => '%%%%%%%%'])->assertSessionHas('checkin.0', 'bad');
        $this->assertNull($ticket->fresh()->checked_in_at);
    }

    public function test_concurrent_scan_does_not_validate_a_ticket_twice(): void
    {
        $this->seed();
        $type = TicketType::with('event.organizer')->firstOrFail();
        $order = Order::create(['user_id' => User::factory()->create()->id, 'event_id' => $type->event_id, 'type' => 'billets', 'status' => 'terminee']);
        $ticket = Ticket::create(['user_id' => $order->user_id, 'order_id' => $order->id, 'ticket_type_id' => $type->id, 'code' => (string) Str::uuid(), 'holder_name' => 'Test']);
        $competingScan = now()->subMinute()->startOfSecond();
        $raced = false;
        Ticket::retrieved(function (Ticket $loaded) use ($ticket, $competingScan, &$raced) {
            if ($loaded->id === $ticket->id && ! $raced) {
                $raced = true;
                DB::table('tickets')->where('id', $ticket->id)->update(['checked_in_at' => $competingScan]);
            }
        });
        $this->actingAs($type->event->organizer)->post('/controle-acces', ['code' => $ticket->code])->assertSessionHas('checkin.0', 'warn');
        $this->assertTrue($ticket->fresh()->checked_in_at->equalTo($competingScan));
    }
}

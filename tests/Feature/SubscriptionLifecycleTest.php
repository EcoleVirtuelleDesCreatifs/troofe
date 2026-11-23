<?php

namespace Tests\Feature;

use App\Models\Payment;
use App\Models\Plan;
use App\Models\Restaurant;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\Billing\PaymentReceived;
use App\Notifications\Billing\SubscriptionActivated;
use App\Notifications\Billing\SubscriptionExpired;
use App\Notifications\Billing\SubscriptionPastDue;
use App\Notifications\Billing\SubscriptionRenewalReminder;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class SubscriptionLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_subscribe_pay_remind_expire(): void
    {
        Notification::fake();
        $this->seed(PlanSeeder::class);
        $user = User::factory()->create(['roles' => ['restaurateur']]);
        $pro = Plan::firstWhere('slug', 'pro');

        // Souscription puis paiement simulé
        $this->actingAs($user)->post(route('subscription.subscribe', $pro), ['cycle' => 'monthly'])->assertRedirect();
        $payment = Payment::firstOrFail();
        $this->post(route('payments.decide', $payment), ['decision' => 'paid', 'method' => 'OM'])->assertRedirect(route('payments.return', $payment));

        $sub = Subscription::firstOrFail();
        $this->assertSame('active', $sub->status);
        $this->assertNotNull($payment->fresh()->invoice_number);
        Notification::assertSentTo($user, SubscriptionActivated::class);
        Notification::assertSentTo($user, PaymentReceived::class);

        // Rappel J-7
        $sub->update(['ends_at' => now()->addDays(6)->addHour()]);
        $this->artisan('troofe:subscriptions')->assertSuccessful();
        Notification::assertSentTo($user, SubscriptionRenewalReminder::class);

        // Échéance dépassée puis expiration après délai de grâce
        $sub->update(['ends_at' => now()->subHour()]);
        $this->artisan('troofe:subscriptions');
        $this->assertSame('past_due', $sub->fresh()->status);
        Notification::assertSentTo($user, SubscriptionPastDue::class);

        // Fiches au-delà de la limite gratuite masquées à l'expiration
        foreach (range(1, 3) as $i) {
            Restaurant::create(['owner_id' => $user->id, 'name' => "R$i", 'cuisine' => 'Ivoirienne', 'city' => 'Abidjan', 'country' => 'CI', 'status' => 'published']);
        }
        $sub->update(['ends_at' => now()->subDays(5)]);
        $this->artisan('troofe:subscriptions');
        $this->assertSame('expired', $sub->fresh()->status);
        Notification::assertSentTo($user, SubscriptionExpired::class);
        $this->assertSame(1, Restaurant::where('owner_id', $user->id)->where('status', 'published')->count());
    }

    public function test_billing_notifications_ignore_opt_out(): void
    {
        $user = User::factory()->create(['notification_settings' => ['facturation' => false, 'commandes' => false]]);
        $this->assertTrue($user->wantsMail('facturation'));
        $this->assertFalse($user->wantsMail('commandes'));
    }
}

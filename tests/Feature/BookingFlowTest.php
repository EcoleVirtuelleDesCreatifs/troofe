<?php

namespace Tests\Feature;

use App\Models\Talent;
use App\Models\User;
use App\Notifications\Culture\BookingConfirmationToClient;
use App\Notifications\Culture\BookingRequested;
use App\Notifications\Culture\BookingStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class BookingFlowTest extends TestCase
{
    use RefreshDatabase;

    private function troupe(User $owner): Talent
    {
        $t = Talent::create(['type' => 'troupe', 'owner_id' => $owner->id, 'name' => 'Troupe test', 'tagline' => 'Danse', 'category' => 'Danse traditionnelle',
            'city' => 'Abidjan', 'country' => 'Côte d\'Ivoire', 'status' => 'published', 'min_notice_days' => 5]);
        $t->offers()->create(['name' => 'Spectacle', 'price' => 200000, 'duration' => '1 h']);

        return $t;
    }

    public function test_full_booking_cycle_notifies_both_parties(): void
    {
        Notification::fake();
        $owner = User::factory()->create(['roles' => ['artiste']]);
        $client = User::factory()->create();
        $t = $this->troupe($owner);

        $this->actingAs($client)->post(route('bookings.store', $t), [
            'kind' => 'prestation', 'talent_offer_id' => $t->offers->first()->id, 'event_date' => now()->addDays(20)->toDateString(),
            'city' => 'Abidjan', 'contact_phone' => '0700000000', 'occasion' => 'Mariages', 'guests' => 100,
        ])->assertRedirect(route('account.bookings'));

        $booking = $t->bookings()->firstOrFail();
        Notification::assertSentTo($owner, BookingRequested::class);
        Notification::assertSentTo($client, BookingConfirmationToClient::class);

        $this->actingAs($owner)->post(route('bookings.transition', [$booking, 'devis_envoye']), ['amount' => 450000, 'quote_note' => 'Acompte 30 %'])->assertRedirect();
        Notification::assertSentTo($client, BookingStatusChanged::class);

        $this->actingAs($client)->post(route('bookings.transition', [$booking, 'confirmee']))->assertRedirect();
        $this->assertSame('confirmee', $booking->fresh()->status);
        Notification::assertSentTo($owner, BookingStatusChanged::class);

        // La date est désormais indisponible pour un autre client
        $this->assertTrue($t->fresh()->busyDates()->contains($booking->event_date->toDateString()));
    }

    public function test_date_inside_notice_period_is_rejected(): void
    {
        $owner = User::factory()->create(['roles' => ['artiste']]);
        $t = $this->troupe($owner);
        $this->actingAs(User::factory()->create())->post(route('bookings.store', $t), [
            'kind' => 'prestation', 'talent_offer_id' => $t->offers->first()->id, 'event_date' => now()->addDays(2)->toDateString(),
            'city' => 'Abidjan', 'contact_phone' => '0700000000',
        ])->assertSessionHasErrors('event_date');
    }

    public function test_client_cannot_send_a_quote(): void
    {
        $owner = User::factory()->create(['roles' => ['artiste']]);
        $client = User::factory()->create();
        $t = $this->troupe($owner);
        $b = $t->bookings()->create(['user_id' => $client->id, 'kind' => 'prestation', 'contact_phone' => '0', 'status' => 'nouvelle']);
        $this->actingAs($client)->post(route('bookings.transition', [$b, 'devis_envoye']), ['amount' => 1])->assertForbidden();
    }
}

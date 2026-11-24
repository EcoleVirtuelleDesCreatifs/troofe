<?php

namespace Tests\Feature;

use App\Models\Article;
use App\Models\ContactMessage;
use App\Models\Dish;
use App\Models\Event;
use App\Models\NewsletterSubscriber;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Restaurant;
use App\Models\Talent;
use App\Models\Ticket;
use App\Models\TicketType;
use App\Models\User;
use App\Notifications\Account\VerifyEmailNotification;
use App\Notifications\Account\WelcomeNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class OperationalFlowsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Notification::fake();
    }

    private function assertPagesWork(array $paths): void
    {
        $failures = [];
        foreach ($paths as $path) {
            $response = $this->get($path);
            if ($response->status() !== 200 || str_contains($response->getContent(), '@__raw_block_')) {
                $failures[] = $path.' => '.$response->status();
            }
        }
        $this->assertSame([], $failures);
    }

    public function test_public_catalogues_and_published_details_are_available_in_both_languages(): void
    {
        $this->seed();
        $paths = [];
        foreach (['fr', 'en'] as $locale) {
            foreach (['', '/restaurants', '/encyclopedie', '/actualites', '/evenements', '/culture', '/danse', '/prestations', '/artisans', '/offres-pro', '/annonceurs', '/devenir-partenaire', '/contact', '/page/a-propos', '/recherche?q=jollof'] as $path) {
                $paths[] = '/'.$locale.$path;
            }
            foreach ([Restaurant::class => 'restaurants', Dish::class => 'encyclopedie', Article::class => 'actualites', Event::class => 'evenements'] as $class => $path) {
                $record = $class::published()->firstOrFail();
                $paths[] = '/'.$locale.'/'.$path.'/'.$record->slug;
            }
            foreach (Talent::TYPES as $type => $meta) {
                $talent = Talent::published()->ofType($type)->firstOrFail();
                $paths[] = '/'.$locale.'/'.$meta['route'].'/'.$talent->slug;
            }
        }
        $this->assertPagesWork($paths);
    }

    public function test_contact_form_accepts_the_empty_honeypot_sent_by_the_browser(): void
    {
        $this->from('/fr/contact')->post('/contact', ['name' => 'Client Test', 'email' => 'client@example.test', 'subject' => 'Demande de partenariat', 'message' => 'Bonjour, je souhaite présenter mon activité.', 'website' => ''])->assertSessionHasNoErrors()->assertRedirect('/fr/contact');
        $this->assertDatabaseCount('contact_messages', 1);
        $this->assertSame('client@example.test', ContactMessage::firstOrFail()->email);
        Notification::assertSentOnDemand(\App\Notifications\Engagement\ContactMessageReceived::class);
    }

    public function test_registration_verification_and_logout_form_a_complete_flow(): void
    {
        $this->post('/inscription', ['name' => 'Membre Test', 'email' => 'membre@example.test', 'password' => 'Password123', 'password_confirmation' => 'Password123', 'terms' => '1', 'newsletter' => '1'])->assertSessionHasNoErrors()->assertRedirect(route('verification.notice'));
        $user = User::firstWhere('email', 'membre@example.test');
        $this->assertNotNull($user);
        $this->assertFalse($user->hasVerifiedEmail());
        Notification::assertSentTo($user, VerifyEmailNotification::class);
        $url = URL::temporarySignedRoute('verification.verify', now()->addHour(), ['id' => $user->id, 'hash' => sha1($user->email)]);
        $this->actingAs($user->fresh())->get($url)->assertRedirect(route('account.dashboard'));
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
        $this->assertNotNull(NewsletterSubscriber::firstOrFail()->confirmed_at);
        Notification::assertSentTo($user, WelcomeNotification::class);
        $this->post('/deconnexion')->assertRedirect(route('home'));
        $this->assertGuest();
        $this->post('/connexion', ['email' => $user->email, 'password' => 'Password123'])->assertRedirect(route('account.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_password_reset_and_login_work_without_real_mail(): void
    {
        $user = User::factory()->create();
        $token = Password::createToken($user);
        $this->post('/reinitialiser', ['token' => $token, 'email' => $user->email, 'password' => 'Changed12345', 'password_confirmation' => 'Changed12345'])->assertSessionHasNoErrors()->assertRedirect(route('login'));
        $this->assertTrue(Hash::check('Changed12345', $user->fresh()->password));
        $this->post('/connexion', ['email' => $user->email, 'password' => 'Changed12345'])->assertRedirect(route('account.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_member_professional_and_admin_pages_are_operational(): void
    {
        $this->seed();
        $user = User::factory()->create(['roles' => ['admin', 'restaurateur', 'organisateur', 'artiste', 'annonceur']]);
        $this->actingAs($user);
        $this->assertPagesWork(['/compte', '/compte/profil', '/compte/notifications', '/compte/commandes', '/compte/billets', '/compte/reservations', '/compte/favoris', '/compte/abonnements', '/compte/abonnement', '/pro', '/pro/restaurants', '/pro/restaurants/create', '/pro/talent', '/pro/talent/create', '/pro/events', '/pro/events/create', '/pro/orders', '/pro/bookings', '/pro/campaigns', '/pro/reviews', '/admin', '/admin/users', '/admin/restaurants', '/admin/restaurants/create', '/admin/talent', '/admin/talent/create', '/admin/events', '/admin/events/create', '/admin/orders', '/admin/bookings', '/admin/payments', '/admin/subscriptions', '/admin/plans', '/admin/ad-campaigns', '/admin/ad-slots', '/admin/articles', '/admin/dishes', '/admin/reviews', '/admin/newsletter-subscribers', '/admin/contact-messages', '/admin/activity-logs']);
    }

    public function test_regular_members_cannot_enter_professional_or_admin_panels(): void
    {
        $user = User::factory()->create(['roles' => ['membre']]);
        $this->actingAs($user)->get('/admin')->assertForbidden();
        $this->get('/pro')->assertForbidden();
    }

    public function test_food_order_payment_and_private_order_detail_work(): void
    {
        $this->seed();
        $buyer = User::factory()->create();
        $restaurant = Restaurant::published()->where('accepts_orders', true)->whereHas('menuItems', fn ($query) => $query->where('is_available', true))->firstOrFail();
        $item = $restaurant->menuItems()->where('is_available', true)->firstOrFail();
        $this->actingAs($buyer)->post(route('orders.food', $restaurant), ['items' => [$item->id => 2], 'fulfilment' => 'retrait', 'contact_phone' => 'TEST', 'total' => 1])->assertSessionHasNoErrors()->assertRedirect();
        $order = Order::where('user_id', $buyer->id)->firstOrFail();
        $this->assertSame($item->price * 2, $order->total);
        $payment = Payment::where('user_id', $buyer->id)->firstOrFail();
        $this->post(route('payments.decide', $payment), ['decision' => 'paid', 'method' => 'OM'])->assertRedirect();
        $this->assertSame('payee', $order->fresh()->status);
        $this->get(route('account.orders.show', $order))->assertOk();
        $this->actingAs(User::factory()->create())->get(route('account.orders.show', $order))->assertForbidden();
    }

    public function test_ticket_purchase_payment_scan_and_duplicate_scan_work(): void
    {
        $this->seed();
        $buyer = User::factory()->create();
        $type = TicketType::with('event')->where('price', '>', 0)->where('quantity', '>', 5)->firstOrFail();
        $sold = $type->sold;
        $this->actingAs($buyer)->post(route('orders.tickets', $type->event), ['tickets' => [$type->id => 2], 'holders' => ['Client A', 'Client B'], 'contact_phone' => 'TEST'])->assertSessionHasNoErrors()->assertRedirect();
        $payment = Payment::where('user_id', $buyer->id)->firstOrFail();
        $this->post(route('payments.decide', $payment), ['decision' => 'paid', 'method' => 'OM'])->assertRedirect();
        $this->assertSame($sold + 2, $type->fresh()->sold);
        $tickets = Ticket::where('user_id', $buyer->id)->get();
        $this->assertCount(2, $tickets);
        $ticket = $tickets->first();
        $this->get(route('tickets.show', $ticket->code))->assertOk();
        $this->actingAs($type->event->organizer)->post('/controle-acces', ['code' => $ticket->code])->assertSessionHas('checkin.0', 'ok');
        $this->assertNotNull($ticket->fresh()->checked_in_at);
        $this->post('/controle-acces', ['code' => $ticket->code])->assertSessionHas('checkin.0', 'warn');
    }
}

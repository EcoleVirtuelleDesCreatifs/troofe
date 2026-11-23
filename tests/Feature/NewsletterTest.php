<?php

namespace Tests\Feature;

use App\Models\NewsletterSubscriber;
use App\Notifications\Newsletter\ConfirmSubscription;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class NewsletterTest extends TestCase
{
    use RefreshDatabase;

    public function test_double_opt_in_and_one_click_unsubscribe(): void
    {
        Notification::fake();
        $this->post(route('newsletter.subscribe'), ['email' => 'Fan@Example.com', 'topics' => ['culture']])->assertRedirect();
        $sub = NewsletterSubscriber::firstOrFail();
        $this->assertSame('fan@example.com', $sub->email);
        $this->assertFalse((bool) $sub->isActive());
        Notification::assertSentTo($sub, ConfirmSubscription::class);

        $this->get(route('newsletter.confirm', $sub->token))->assertOk();
        $this->assertTrue($sub->fresh()->isActive());

        $this->post(route('newsletter.unsubscribe', $sub->token))->assertOk();
        $this->assertNotNull($sub->fresh()->unsubscribed_at);
    }

    public function test_bots_are_rejected_by_honeypot(): void
    {
        $this->post(route('newsletter.subscribe'), ['email' => 'bot@spam.com', 'website' => 'http://spam'])->assertSessionHasErrors('website');
    }
}

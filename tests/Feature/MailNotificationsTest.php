<?php

namespace Tests\Feature;

use App\Models\Booking;
use App\Models\NewsletterSubscriber;
use App\Models\Payment;
use App\Models\Talent;
use App\Models\User;
use App\Notifications\Account\ResetPasswordNotification;
use App\Notifications\Account\VerifyEmailNotification;
use App\Notifications\Account\WelcomeNotification;
use App\Notifications\Billing\PaymentReceived;
use App\Notifications\Culture\BookingConfirmationToClient;
use App\Notifications\Newsletter\ConfirmSubscription;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class MailNotificationsTest extends TestCase
{
    public function test_representative_notifications_render_professional_html_and_plain_text(): void
    {
        $user = new User(['name' => 'Client Test', 'email' => 'client@example.test']);
        $user->id = 1;
        $payment = new Payment(['reference' => 'TEST-001', 'amount' => 15000, 'description' => 'Réservation de démonstration', 'method' => 'Mobile Money']);
        $payment->id = 1;
        $booking = new Booking(['reference' => 'RSV-TEST']);
        $booking->setRelation('talent', new Talent(['name' => 'Atelier Test']));
        $subscriber = new NewsletterSubscriber(['email' => 'lecteur@example.test', 'token' => 'test-token-not-valid-in-production']);
        $cases = [
            [new WelcomeNotification, $user],
            [new VerifyEmailNotification, $user],
            [new ResetPasswordNotification('test-token-not-valid-in-production'), $user],
            [new PaymentReceived($payment), $user],
            [new BookingConfirmationToClient($booking), $user],
            [new ConfirmSubscription, $subscriber],
        ];

        foreach ($cases as [$notification, $recipient]) {
            $message = $notification->toMail($recipient);
            $html = (string) $message->render();
            $this->assertNotEmpty($message->subject);
            $this->assertStringContainsString('Trôofê', $html);
            $this->assertStringContainsString('brand-wordmark', $html);
            $this->assertStringNotContainsString('<img', $html);
            $this->assertStringContainsString('Gastronomie, culture', $html);
            $this->assertStringContainsString('Préférences de notification', $html);
            $this->assertStringContainsString('button', $html);
            $this->assertStringNotContainsString('@__raw_block_', $html);
            $text = app(\Illuminate\Mail\Markdown::class)->renderText($message->markdown, $message->data());
            $this->assertStringContainsString('Trôofê', (string) $text);
        }
    }

    public function test_welcome_has_structured_sections_without_raw_urls_or_broken_images(): void
    {
        $user = new User(['name' => '<script>alert(1)</script>', 'email' => 'client@example.test']);
        $html = (string) (new WelcomeNotification)->toMail($user)->render();
        $this->assertStringContainsString('Votre prochaine découverte commence ici.', $html);
        $this->assertStringContainsString('Découvrir les offres professionnelles', $html);
        $this->assertStringNotContainsString("If you're having trouble", $html);
        $this->assertStringNotContainsString('<script>', $html);
        $document = new \DOMDocument;
        @$document->loadHTML('<?xml encoding="UTF-8">'.$html);
        $xpath = new \DOMXPath($document);
        $this->assertSame(3, $xpath->query('//table[contains(@class, "feature-list")]/tr')->length);
        $this->assertSame(0, $xpath->query('//h1//p | //img')->length);
        $this->assertStringNotContainsString('http://localhost', $document->textContent);
        $this->assertStringContainsString('font-weight: 400', $html);
    }

    public function test_mail_channel_delivers_to_memory_without_smtp_or_database_writes(): void
    {
        config(['mail.default' => 'array', 'mail.mailers.array' => ['transport' => 'array']]);
        $user = new User(['name' => 'Client Test', 'email' => 'client@example.test']);
        Notification::sendNow($user, new WelcomeNotification, ['mail']);
        $messages = Mail::mailer('array')->getSymfonyTransport()->messages();
        $this->assertCount(1, $messages);
        $email = $messages->first()->getOriginalMessage();
        $this->assertSame('client@example.test', $email->getTo()[0]->getAddress());
        $this->assertSame('Bienvenue sur Trôofê', $email->getSubject());
        $this->assertNotEmpty($email->getHtmlBody());
        $this->assertNotEmpty($email->getTextBody());
    }
}

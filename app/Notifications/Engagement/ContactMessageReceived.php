<?php

namespace App\Notifications\Engagement;

use App\Models\ContactMessage;
use App\Notifications\TroofeNotification;
use Illuminate\Notifications\Messages\MailMessage;

class ContactMessageReceived extends TroofeNotification
{
    public function __construct(public ContactMessage $contact) {}

    public function category(): string { return 'compte'; }

    public function title(): string { return __('Contact : :subject', ['subject' => $this->contact->subject]); }

    public function message(): string { return $this->contact->name.' ('.$this->contact->email.')'; }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)->subject('[Trôofê] '.$this->title())
            ->replyTo($this->contact->email, $this->contact->name)
            ->line($this->message())
            ->line($this->contact->message);
    }
}

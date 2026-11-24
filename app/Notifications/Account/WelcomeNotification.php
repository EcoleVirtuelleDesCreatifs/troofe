<?php

namespace App\Notifications\Account;

use App\Notifications\TroofeNotification;
use Illuminate\Notifications\Messages\MailMessage;

class WelcomeNotification extends TroofeNotification
{
    public function category(): string { return 'compte'; }

    public function title(): string { return __('Bienvenue sur Trôofê'); }

    public function message(): string { return __('Votre compte est actif. Découvrez les restaurants, l\'agenda, les artistes et les artisans.'); }

    public function url(): string { return route('home'); }

    public function icon(): string { return 'bi-stars'; }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->mailBase($notifiable)
            ->markdown('mail.welcome', ['name' => $notifiable->name ?? __('cher membre')]);
    }
}

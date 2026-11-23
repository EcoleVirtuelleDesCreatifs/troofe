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
            ->line(__('Votre compte est confirmé. Voici ce que vous pouvez faire dès maintenant :'))
            ->line('• '.__('commander dans les restaurants et réserver vos billets d\'événements ;'))
            ->line('• '.__('réserver un groupe de danse, un artiste ou commander à un artisan ;'))
            ->line('• '.__('suivre vos adresses préférées pour être prévenu de leurs nouveautés.'))
            ->action(__('Explorer Trôofê'), route('home'))
            ->line(__('Vous êtes professionnel ? Créez votre fiche depuis l\'espace pro : :url', ['url' => route('pricing')]));
    }
}

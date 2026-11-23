<?php

namespace App\Notifications\Account;

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;

class VerifyEmailNotification extends VerifyEmail
{
    public function toMail($notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Confirmez votre adresse e-mail'))
            ->greeting(__('Bienvenue sur Trôofê, :name !', ['name' => $notifiable->name]))
            ->line(__('Cliquez sur le bouton ci-dessous pour confirmer votre adresse e-mail et activer votre compte.'))
            ->action(__('Confirmer mon adresse'), $this->verificationUrl($notifiable))
            ->line(__('Ce lien est valable 60 minutes. Si vous n\'avez pas créé de compte, ignorez ce message.'))
            ->salutation(__('L\'équipe Trôofê'));
    }
}

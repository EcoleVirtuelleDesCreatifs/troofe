<?php

namespace App\Notifications\Account;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Notifications\Messages\MailMessage;

class ResetPasswordNotification extends ResetPassword
{
    public function toMail($notifiable): MailMessage
    {
        $url = route('password.reset', ['token' => $this->token, 'email' => $notifiable->getEmailForPasswordReset()]);

        return (new MailMessage)
            ->subject(__('Réinitialisation de votre mot de passe'))
            ->greeting(__('Bonjour :name,', ['name' => $notifiable->name]))
            ->line(__('Vous avez demandé à réinitialiser le mot de passe de votre compte Trôofê.'))
            ->action(__('Choisir un nouveau mot de passe'), $url)
            ->line(__('Ce lien expire dans 60 minutes. Si vous n\'êtes pas à l\'origine de cette demande, aucune action n\'est nécessaire.'))
            ->salutation(__('L\'équipe Trôofê'));
    }
}

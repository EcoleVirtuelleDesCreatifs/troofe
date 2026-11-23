<?php

namespace App\Notifications\Newsletter;

use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Double opt-in obligatoire avant tout envoi de lettre d'information */
class ConfirmSubscription extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(NewsletterSubscriber $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('Confirmez votre inscription à la lettre Trôofê'))
            ->greeting(__('Bonjour,'))
            ->line(__('Vous avez demandé à recevoir la lettre d\'information Trôofê : nouvelles adresses, agenda, artistes et recettes.'))
            ->action(__('Confirmer mon inscription'), route('newsletter.confirm', $notifiable->token))
            ->line(__('Sans confirmation, vous ne recevrez rien. Vous pourrez vous désinscrire en un clic à tout moment.'))
            ->salutation(__('L\'équipe Trôofê'));
    }
}

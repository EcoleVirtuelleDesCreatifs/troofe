<?php

namespace App\Notifications\Newsletter;

use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** Lettre d'information périodique, construite selon les thèmes choisis */
class Digest extends Notification implements ShouldQueue
{
    use Queueable;

    /** @param array<string, array<int, array{title:string,line:string,url:string}>> $sections */
    public function __construct(public array $sections) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(NewsletterSubscriber $notifiable): MailMessage
    {
        $topics = config('troofe.newsletter_topics');
        $mail = (new MailMessage)
            ->subject(__('Cette semaine sur Trôofê'))
            ->greeting($notifiable->name ? __('Bonjour :name,', ['name' => $notifiable->name]) : __('Bonjour,'))
            ->line(__('Voici la sélection de la rédaction.'));

        foreach ($this->sections as $topic => $items) {
            if (! $notifiable->wants($topic) || ! $items) {
                continue;
            }
            $mail->line('**'.__($topics[$topic] ?? $topic).'**');
            foreach ($items as $i) {
                $mail->line('• ['.$i['title'].']('.$i['url'].') — '.$i['line']);
            }
        }

        return $mail->action(__('Tout voir sur Trôofê'), route('home'))
            ->line(__('Gérer mes thèmes ou me désinscrire : :url', ['url' => route('newsletter.manage', $notifiable->token)]))
            ->salutation(__('L\'équipe Trôofê'))
            ->withSymfonyMessage(function ($message) use ($notifiable) {
                // Désinscription en un clic depuis le logiciel de messagerie
                $message->getHeaders()->addTextHeader('List-Unsubscribe', '<'.route('newsletter.unsubscribe', $notifiable->token).'>');
                $message->getHeaders()->addTextHeader('List-Unsubscribe-Post', 'List-Unsubscribe=One-Click');
            });
    }
}

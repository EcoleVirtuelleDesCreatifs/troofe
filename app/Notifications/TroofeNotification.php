<?php

namespace App\Notifications;

use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Base de toutes les notifications Trôofê :
 * - envoi en file d'attente (pas d'attente pour l'utilisateur) ;
 * - canal « database » (cloche du site) pour les comptes ;
 * - e-mail selon les préférences, sauf catégories obligatoires ;
 * - rédaction dans la langue du destinataire (HasLocalePreference).
 */
abstract class TroofeNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $backoff = 60;

    abstract public function category(): string;

    abstract public function title(): string;

    abstract public function message(): string;

    public function url(): ?string
    {
        return null;
    }

    public function icon(): string
    {
        return 'bi-bell';
    }

    public function via(object $notifiable): array
    {
        if (! $notifiable instanceof User) {
            return ['mail'];
        }

        return $notifiable->wantsMail($this->category()) ? ['database', 'mail'] : ['database'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'message' => $this->message(),
            'url' => $this->url(),
            'icon' => $this->icon(),
            'category' => $this->category(),
        ];
    }

    /** Gabarit commun des e-mails */
    protected function mailBase(object $notifiable): MailMessage
    {
        $name = $notifiable instanceof User ? $notifiable->name : ($notifiable->name ?? null);

        $mail = (new MailMessage)
            ->subject($this->title())
            ->greeting($name ? __('Bonjour :name,', ['name' => $name]) : __('Bonjour,'))
            ->salutation(__('L\'équipe Trôofê'));

        return $mail;
    }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = $this->mailBase($notifiable)->line($this->message());
        if ($this->url()) {
            $mail->action($this->actionLabel(), $this->url());
        }

        return $this->footer($mail, $notifiable);
    }

    protected function actionLabel(): string
    {
        return __('Voir le détail');
    }

    protected function footer(MailMessage $mail, object $notifiable): MailMessage
    {
        if ($notifiable instanceof User && ! config('troofe.notification_categories.'.$this->category().'.mandatory')) {
            $mail->line(__('Vous recevez cet e-mail selon vos préférences de notification. Modifiez-les ici : :url', ['url' => route('account.notifications')]));
        }

        return $mail;
    }
}

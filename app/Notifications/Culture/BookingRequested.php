<?php

namespace App\Notifications\Culture;

use App\Models\Booking;
use App\Notifications\TroofeNotification;
use Illuminate\Notifications\Messages\MailMessage;

/** Au prestataire : nouvelle demande */
class BookingRequested extends TroofeNotification
{
    public function __construct(public Booking $booking) {}

    public function category(): string { return 'reservations'; }

    public function title(): string { return __('Nouvelle demande :ref pour « :name »', ['ref' => $this->booking->reference, 'name' => $this->booking->talent->name]); }

    public function message(): string
    {
        $b = $this->booking;

        return match ($b->kind) {
            'commande' => __(':client commande :qty × :item (:amount indicatif).', ['client' => $b->user->name, 'qty' => $b->quantity, 'item' => $b->offer?->name, 'amount' => money($b->amount)]),
            'visite' => __(':client souhaite visiter l\'atelier le :date (:count personne(s)).', ['client' => $b->user->name, 'date' => date_human($b->event_date), 'count' => $b->guests]),
            default => __(':client demande « :offer » le :date à :city.', ['client' => $b->user->name, 'offer' => $b->offer?->name, 'date' => date_human($b->event_date), 'city' => $b->city]),
        };
    }

    public function url(): string { return url('/pro/bookings'); }

    public function icon(): string { return 'bi-calendar-plus'; }

    public function toMail(object $notifiable): MailMessage
    {
        $b = $this->booking;
        $mail = $this->mailBase($notifiable)->line($this->message());
        if ($b->occasion) { $mail->line(__('Occasion : :o', ['o' => __($b->occasion)])); }
        if ($b->guests && $b->kind === 'prestation') { $mail->line(__('Invités : :n', ['n' => $b->guests])); }
        if ($b->venue) { $mail->line(__('Lieu : :v', ['v' => $b->venue])); }
        if ($b->message) { $mail->line(__('Message : « :m »', ['m' => $b->message])); }

        return $mail->line(__('Contact : :phone', ['phone' => $b->contact_phone]))
            ->action($b->kind === 'prestation' ? __('Envoyer un devis') : __('Répondre à la demande'), $this->url())
            ->line(__('Répondez sous 48 h : les prestataires réactifs sont mieux classés.'));
    }
}

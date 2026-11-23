<?php

namespace App\Notifications\Culture;

use App\Models\Booking;
use App\Notifications\TroofeNotification;
use Illuminate\Notifications\Messages\MailMessage;

/** Évolution d'une réservation, envoyée à l'autre partie */
class BookingStatusChanged extends TroofeNotification
{
    public function __construct(public Booking $booking, public bool $toOwner = false) {}

    public function category(): string { return 'reservations'; }

    public function title(): string
    {
        $b = $this->booking;

        return match ($b->status) {
            'devis_envoye' => __('Devis reçu de :name : :amount', ['name' => $b->talent->name, 'amount' => money($b->amount)]),
            'confirmee' => $this->toOwner ? __(':client a accepté votre devis (:ref)', ['client' => $b->user->name, 'ref' => $b->reference]) : __(':name confirme votre réservation :ref', ['name' => $b->talent->name, 'ref' => $b->reference]),
            'refusee' => __(':name ne peut pas donner suite (:ref)', ['name' => $b->talent->name, 'ref' => $b->reference]),
            'annulee' => __('Réservation :ref annulée', ['ref' => $b->reference]),
            'realisee' => __('Comment s\'est passée la prestation de :name ?', ['name' => $b->talent->name]),
            default => __('Réservation :ref mise à jour', ['ref' => $b->reference]),
        };
    }

    public function message(): string
    {
        $b = $this->booking;

        return match ($b->status) {
            'devis_envoye' => __('Montant proposé : :amount. :note Acceptez le devis pour bloquer la date.', ['amount' => money($b->amount), 'note' => $b->quote_note]),
            'confirmee' => $b->event_date ? __('Date retenue : :date.', ['date' => date_human($b->event_date)]) : __('La commande passe en fabrication.'),
            'refusee' => __('La date ou la demande ne convient pas. D\'autres prestataires sont disponibles sur Trôofê.'),
            'annulee' => __('La demande a été annulée par :who.', ['who' => $this->toOwner ? $b->user->name : __('le prestataire ou l\'équipe Trôofê')]),
            'realisee' => __('Votre avis aide les autres clients à choisir.'),
            default => '',
        };
    }

    public function url(): string
    {
        if ($this->toOwner) {
            return url('/pro/bookings');
        }

        return $this->booking->status === 'realisee' ? $this->booking->talent->url().'#avis' : route('account.bookings');
    }

    public function icon(): string { return 'bi-calendar2-check'; }

    protected function actionLabel(): string
    {
        return match (true) {
            $this->booking->status === 'devis_envoye' && ! $this->toOwner => __('Voir et accepter le devis'),
            $this->booking->status === 'realisee' && ! $this->toOwner => __('Donner mon avis'),
            default => __('Voir la réservation'),
        };
    }

    public function toMail(object $notifiable): MailMessage
    {
        return parent::toMail($notifiable);
    }
}

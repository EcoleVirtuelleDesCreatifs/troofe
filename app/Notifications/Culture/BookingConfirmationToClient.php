<?php

namespace App\Notifications\Culture;

use App\Models\Booking;
use App\Notifications\TroofeNotification;

/** Au client : accusé de réception */
class BookingConfirmationToClient extends TroofeNotification
{
    public function __construct(public Booking $booking) {}

    public function category(): string { return 'reservations'; }

    public function title(): string { return __('Demande :ref envoyée à :name', ['ref' => $this->booking->reference, 'name' => $this->booking->talent->name]); }

    public function message(): string { return __('Votre demande a bien été transmise. Vous serez prévenu dès que :name vous répond avec un devis ou une confirmation. Rien n\'est dû avant votre accord.', ['name' => $this->booking->talent->name]); }

    public function url(): string { return route('account.bookings'); }

    public function icon(): string { return 'bi-send-check'; }

    protected function actionLabel(): string { return __('Suivre ma demande'); }
}

<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\Talent;
use App\Models\User;
use App\Notifications\Culture\BookingConfirmationToClient;
use App\Notifications\Culture\BookingRequested;
use App\Notifications\Culture\BookingStatusChanged;
use Illuminate\Support\Facades\Notification;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function create(User $user, Talent $talent, array $data): Booking
    {
        if (! $talent->isPublished()) {
            abort(404);
        }

        $offer = isset($data['talent_offer_id']) ? $talent->offers()->find($data['talent_offer_id']) : null;

        if (($data['kind'] ?? 'prestation') !== 'commande' && ! empty($data['event_date'])) {
            if ($data['event_date'] < $talent->earliestDate()) {
                throw ValidationException::withMessages(['event_date' => __(':name demande :n jours de délai : première date possible le :date.', ['name' => $talent->name, 'n' => $talent->min_notice_days, 'date' => date_human($talent->earliestDate())])]);
            }
            if (($data['kind'] ?? 'prestation') === 'prestation' && $talent->busyDates()->contains($data['event_date'])) {
                throw ValidationException::withMessages(['event_date' => __(':name est déjà réservé le :date.', ['name' => $talent->name, 'date' => date_human($data['event_date'])])]);
            }
        }

        $amount = match ($data['kind'] ?? 'prestation') {
            'commande' => ($offer?->price ?? 0) * max(1, (int) ($data['quantity'] ?? 1)),
            'visite' => (int) $talent->visit_price * max(1, (int) ($data['guests'] ?? 1)),
            default => 0, // la prestation est chiffrée par le devis
        };

        $booking = Booking::create(array_merge($data, [
            'talent_id' => $talent->id,
            'user_id' => $user->id,
            'talent_offer_id' => $offer?->id,
            'amount' => $amount,
            'status' => 'nouvelle',
        ]));
        $booking->load('talent.owner', 'offer', 'user');

        $user->notify(new BookingConfirmationToClient($booking));
        if ($talent->owner) {
            $talent->owner->notify(new BookingRequested($booking));
        } else {
            // Fiche gérée par l'équipe : l'alerte part à l'administration
            Notification::route('mail', config('troofe.admin_email'))->notify(new BookingRequested($booking));
        }

        return $booking;
    }

    /** Transitions autorisées et notification de l'autre partie */
    public function transition(Booking $booking, string $status, User $by, array $attrs = []): void
    {
        $isOwner = $booking->talent->owner_id === $by->id || $by->isAdmin();
        $isClient = $booking->user_id === $by->id;

        $allowed = match (true) {
            $isOwner && $booking->status === 'nouvelle' => $booking->kind === 'prestation' ? ['devis_envoye', 'refusee'] : ['confirmee', 'refusee'],
            $isOwner && $booking->status === 'devis_envoye' => ['devis_envoye', 'refusee'],
            $isOwner && $booking->status === 'confirmee' => ['realisee', 'annulee'],
            $isClient && $booking->status === 'devis_envoye' => ['confirmee', 'annulee'],
            $isClient && $booking->status === 'nouvelle' => ['annulee'],
            default => [],
        };
        if (! in_array($status, $allowed, true)) {
            abort(403, __('Action impossible à ce stade de la réservation.'));
        }

        if ($status === 'confirmee' && $booking->event_date && $booking->kind === 'prestation' && $booking->talent->busyDates()->contains($booking->event_date->toDateString())) {
            throw ValidationException::withMessages(['status' => __('Cette date vient d\'être réservée par un autre client.')]);
        }

        $booking->transition($status, $by, $attrs);
        $booking->load('talent.owner', 'user', 'offer');

        if ($isClient && ! $isOwner) {
            $booking->talent->owner?->notify(new BookingStatusChanged($booking, toOwner: true));
        } else {
            $booking->user->notify(new BookingStatusChanged($booking));
        }
    }
}

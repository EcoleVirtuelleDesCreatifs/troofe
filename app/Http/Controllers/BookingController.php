<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\Talent;
use App\Services\BookingService;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class BookingController extends Controller
{
    public function __construct(private BookingService $bookings) {}

    public function store(Request $request, Talent $talent)
    {
        $kind = $request->input('kind', 'prestation');
        $data = $request->validate([
            'kind' => ['required', Rule::in(['prestation', 'commande', 'visite'])],
            'talent_offer_id' => [Rule::requiredIf($kind !== 'visite'), 'nullable', Rule::exists('talent_offers', 'id')->where('talent_id', $talent->id)],
            'event_date' => [Rule::requiredIf($kind !== 'commande'), 'nullable', 'date', 'after_or_equal:tomorrow'],
            'occasion' => ['nullable', 'string', 'max:60'],
            'city' => [Rule::requiredIf($kind === 'prestation'), 'nullable', 'string', 'max:80'],
            'venue' => ['nullable', 'string', 'max:160'],
            'guests' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'quantity' => [Rule::requiredIf($kind === 'commande'), 'nullable', 'integer', 'min:1', 'max:1000'],
            'delivery_mode' => ['nullable', Rule::in(['retrait', 'livraison'])],
            'contact_phone' => ['required', 'string', 'max:40'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);
        abort_if($kind === 'visite' && ! $talent->visit_price && $talent->visit_price !== 0, 404);

        $booking = $this->bookings->create($request->user(), $talent, $data);

        return redirect()->route('account.bookings')->with('success', __('Demande :ref envoyée à :name. Vous serez prévenu par e-mail de sa réponse.', ['ref' => $booking->reference, 'name' => $talent->name]));
    }

    /** « Composer ma soirée » : plusieurs demandes en une fois */
    public function pack(Request $request)
    {
        $data = $request->validate([
            'offers' => ['required', 'array', 'min:1', 'max:3'],
            'offers.*' => ['integer', 'exists:talent_offers,id'],
            'event_date' => ['required', 'date', 'after_or_equal:tomorrow'],
            'guests' => ['required', 'integer', 'min:1', 'max:5000'],
            'city' => ['required', 'string', 'max:80'],
            'venue' => ['nullable', 'string', 'max:160'],
            'contact_phone' => ['required', 'string', 'max:40'],
        ]);
        $group = 'PK-'.strtoupper(Str::random(5));
        $made = [];
        foreach ($data['offers'] as $offerId) {
            $offer = \App\Models\TalentOffer::with('talent')->find($offerId);
            $made[] = $this->bookings->create($request->user(), $offer->talent, [
                'kind' => 'prestation', 'talent_offer_id' => $offer->id, 'event_date' => $data['event_date'], 'guests' => $data['guests'],
                'city' => $data['city'], 'venue' => $data['venue'], 'contact_phone' => $data['contact_phone'], 'occasion' => 'Autre',
                'message' => __('Demande groupée « Composer ma soirée »'), 'group_reference' => $group,
            ])->reference;
        }

        return redirect()->route('account.bookings')->with('success', __('Demandes envoyées : :refs.', ['refs' => implode(', ', $made)]));
    }

    public function transition(Request $request, Booking $booking, string $status)
    {
        $attrs = [];
        if ($status === 'devis_envoye') {
            $attrs = $request->validate(['amount' => 'required|integer|min:0', 'quote_note' => 'nullable|string|max:1000']);
        }
        $this->bookings->transition($booking, $status, $request->user(), $attrs);

        return back()->with('success', __('Réservation :ref : :status.', ['ref' => $booking->reference, 'status' => mb_strtolower(__(Booking::STATUSES[$status]))]));
    }
}

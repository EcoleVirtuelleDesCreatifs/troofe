<?php

namespace App\Filament\Admin;

use App\Models\Booking;
use App\Models\Event;
use App\Models\Payment;
use App\Models\Restaurant;
use App\Models\Talent;
use App\Models\User;

class Dashboard extends \Filament\Pages\Dashboard
{
    protected static string $view = 'pages.admin-dashboard';

    public function getLayout(): string
    {
        return 'layouts.administration';
    }

    protected function getViewData(): array
    {
        return [
            'indicators' => [
                ['bi-wallet2', __('Encaissé ce mois'), money((int) Payment::where('status', 'paid')->where('paid_at', '>=', now()->startOfMonth())->sum('amount'))],
                ['bi-people', __('Membres'), User::count()],
                ['bi-shop', __('Restaurants'), Restaurant::count()],
                ['bi-calendar-event', __('Événements'), Event::count()],
                ['bi-palette', __('Culture & artisanat'), Talent::count()],
                ['bi-calendar-check', __('Réservations culture'), Booking::count()],
            ],
            'pending' => [
                [__('Restaurants'), Restaurant::where('status', 'pending')->count(), Resources\RestaurantResource::getUrl()],
                [__('Culture & artisanat'), Talent::where('status', 'pending')->count(), Resources\TalentResource::getUrl()],
                [__('Événements'), Event::where('status', 'pending')->count(), Resources\EventResource::getUrl()],
            ],
            'bookings' => Booking::latest()->take(5)->get(),
        ];
    }
}

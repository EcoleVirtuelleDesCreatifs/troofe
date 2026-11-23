<?php

namespace App\Filament\Admin\Widgets;

use App\Models\Booking;
use App\Models\Event;
use App\Models\NewsletterSubscriber;
use App\Models\Payment;
use App\Models\Restaurant;
use App\Models\Subscription;
use App\Models\Talent;
use App\Models\User;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class AdminStats extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $revenue = fn ($from) => Payment::where('status', 'paid')->where('paid_at', '>=', $from)->sum('amount');
        $daily = collect(range(13, 0))->map(fn ($d) => (int) Payment::where('status', 'paid')->whereDate('paid_at', now()->subDays($d))->sum('amount'))->all();
        $pending = Restaurant::where('status', 'pending')->count() + Talent::where('status', 'pending')->count() + Event::where('status', 'pending')->count();

        return [
            Stat::make(__('Encaissé ce mois'), money((int) $revenue(now()->startOfMonth())))->description(__('30 derniers jours : :amount', ['amount' => money((int) $revenue(now()->subDays(30)))]))->chart($daily)->color('success'),
            Stat::make(__('Abonnements pro actifs'), Subscription::whereIn('status', ['active', 'cancelled'])->count())
                ->description(__(':n échéance(s) sous 7 jours', ['n' => Subscription::where('status', 'active')->whereBetween('ends_at', [now(), now()->addDays(7)])->count()])),
            Stat::make(__('Fiches à valider'), $pending)->color($pending ? 'warning' : 'gray')->description(__('Restaurants, culture, événements')),
            Stat::make(__('Membres'), User::count())->description(__(':n cette semaine', ['n' => User::where('created_at', '>=', now()->subWeek())->count()])),
            Stat::make(__('Réservations culture'), Booking::where('created_at', '>=', now()->startOfMonth())->count())->description(__(':n en attente de réponse', ['n' => Booking::where('status', 'nouvelle')->count()])),
            Stat::make(__('Abonnés à la lettre'), NewsletterSubscriber::active()->count())->description(__(':n à confirmer', ['n' => NewsletterSubscriber::whereNull('confirmed_at')->count()])),
        ];
    }
}

<?php

namespace App\Filament\Pro\Widgets;

use App\Models\Booking;
use App\Models\Order;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ProOverview extends StatsOverviewWidget
{
    protected function getStats(): array
    {
        $u = auth()->user();
        $plan = $u->currentPlan();
        $sub = $u->activeSubscription;
        $orders = Order::where('status', '!=', 'en_attente')->where('status', '!=', 'annulee')
            ->where(fn ($query) => $query->whereHas('restaurant', fn ($record) => $record->where('owner_id', $u->id))->orWhereHas('event', fn ($e) => $e->where('organizer_id', $u->id)))
            ->where('created_at', '>=', now()->startOfMonth());
        $views = $u->restaurants()->sum('views') + $u->talents()->sum('views') + $u->organizedEvents()->sum('views');

        return [
            Stat::make(__('Formule :plan', ['plan' => $plan->tr('name')]), __(':used / :max fiches', ['used' => $u->listingsCount(), 'max' => $plan->max_listings]))
                ->description($sub ? __('Échéance le :date', ['date' => date_human($sub->ends_at, 'D MMM YYYY')]) : __('Passer à une formule supérieure'))
                ->url($sub ? route('account.billing') : route('pricing'))->color($sub ? 'success' : 'warning'),
            Stat::make(__('Ventes du mois'), money((int) (clone $orders)->sum('total') - (int) (clone $orders)->sum('commission')))->description(__(':n commande(s), net de commission', ['n' => (clone $orders)->count()])),
            Stat::make(__('Demandes en attente'), Booking::whereHas('talent', fn ($query) => $query->where('owner_id', $u->id))->where('status', 'nouvelle')->count())->description(__('Répondez sous 48 h'))->url(url('/pro/bookings')),
            Stat::make(__('Vues de vos fiches'), number_format($views, 0, ',', ' ')),
        ];
    }
}

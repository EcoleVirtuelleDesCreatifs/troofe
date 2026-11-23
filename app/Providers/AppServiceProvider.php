<?php

namespace App\Providers;

use App\Models\Event;
use App\Models\Restaurant;
use App\Models\Talent;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Paginator::useBootstrapFive();

        // Noms courts et stables pour les relations polymorphes
        Relation::enforceMorphMap([
            'user' => \App\Models\User::class,
            'restaurant' => Restaurant::class,
            'talent' => Talent::class,
            'event' => Event::class,
            'menu_item' => \App\Models\MenuItem::class,
            'ticket_type' => \App\Models\TicketType::class,
            'order' => \App\Models\Order::class,
            'subscription' => \App\Models\Subscription::class,
            'ad_campaign' => \App\Models\AdCampaign::class,
            'payment' => \App\Models\Payment::class,
            'review' => \App\Models\Review::class,
            'booking' => \App\Models\Booking::class,
            'article' => \App\Models\Article::class,
            'dish' => \App\Models\Dish::class,
        ]);

        // Toute modification de contenu public vide le cache de l'accueil
        foreach ([Restaurant::class, Talent::class, Event::class, \App\Models\Dish::class, \App\Models\Article::class] as $model) {
            $model::saved(fn () => collect(['fr', 'en'])->each(fn ($l) => Cache::forget("home:$l")));
        }

        // Compteur de notifications non lues pour la barre du haut
        View::composer('layouts.app', function ($view) {
            $user = auth()->user();
            $view->with('unreadNotifications', $user ? $user->unreadNotifications()->take(6)->get() : collect());
            $view->with('unreadCount', $user ? $user->unreadNotifications()->count() : 0);
        });
    }
}

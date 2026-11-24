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
    public function register(): void
    {
        // Une instance par requête (scoped : remise à zéro entre deux tâches de file d'attente)
        $this->app->scoped(\App\Support\Seo::class);
    }

    public function boot(): void
    {
        Paginator::useBootstrapFive();
        \Illuminate\Support\Facades\URL::defaults(['locale' => 'fr']);

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
        // et les caches SEO (sitemaps, llms.txt, flux RSS) ; les moteurs IndexNow sont prévenus
        foreach ([Restaurant::class, Talent::class, Event::class, \App\Models\Dish::class, \App\Models\Article::class] as $model) {
            $model::saved(function ($m) {
                foreach (['home:fr', 'home:en', 'feed:fr', 'feed:en', 'llms:short', 'llms:full', 'sitemap:v2:index'] as $key) {
                    Cache::forget($key);
                }
                foreach (['pages', 'restaurants', 'culture', 'evenements', 'encyclopedie', 'actualites'] as $section) {
                    Cache::forget("sitemap:v2:$section");
                }
                $public = ($m->status ?? null) === 'published' && ($m->wasChanged('status') || $m->wasRecentlyCreated || $m->wasChanged(['name', 'title', 'description', 'tagline', 'body', 'summary']));
                if ($public) {
                    $url = match (true) {
                        $m instanceof Restaurant => route('restaurants.show', $m),
                        $m instanceof Talent => $m->url(),
                        $m instanceof Event => route('events.show', $m),
                        $m instanceof \App\Models\Dish => route('dishes.show', $m),
                        default => route('articles.show', $m),
                    };
                    \App\Jobs\PingIndexNow::urls($url);
                }
            });
        }

        // Compteur de notifications non lues pour la barre du haut
        View::composer('layouts.app', function ($view) {
            $user = auth()->user();
            $view->with('unreadNotifications', $user ? $user->unreadNotifications()->take(6)->get() : collect());
            $view->with('unreadCount', $user ? $user->unreadNotifications()->count() : 0);
        });
    }
}

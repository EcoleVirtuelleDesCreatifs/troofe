<?php

namespace App\Http\Controllers;

use App\Models\Article;
use App\Models\Dish;
use App\Models\Event;
use App\Models\Restaurant;
use App\Models\Talent;
use App\Support\Placeholder;
use App\Support\SchemaOrg;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    public function home()
    {
        $data = Cache::remember('home:'.app()->getLocale(), 600, fn () => [
            'restaurants' => Restaurant::published()->orderByDesc('is_featured')->orderByDesc('views')->take(8)->get(),
            'events' => Event::published()->upcoming()->with('ticketTypes')->orderBy('starts_at')->take(6)->get(),
            'dishes' => Dish::published()->orderByDesc('views')->take(6)->get(),
            'articles' => Article::published()->latest('published_at')->take(3)->get(),
            'talents' => collect(array_keys(Talent::TYPES))->mapWithKeys(fn ($t) => [$t => Talent::published()->ofType($t)->orderByDesc('is_featured')->orderByDesc('views')->take(3)->get()]),
            'counts' => [
                'restaurants' => Restaurant::published()->count(),
                'dishes' => Dish::published()->count(),
                'events' => Event::published()->upcoming()->count(),
                'talents' => Talent::published()->count(),
            ],
        ]);

        seo()->title(__('Restaurants, culture et gastronomie africaine'))
            ->description(__('Restaurants africains avec commande en ligne, encyclopédie des plats, agenda des événements, groupes de danse, artistes et artisans à réserver à Abidjan, Accra, Dakar et Lagos.'))
            ->schema(SchemaOrg::itemList($data['restaurants'], fn ($r) => route('restaurants.show', $r), fn ($r) => $r->name));

        return view('pages.home', $data);
    }

    public function search(Request $request)
    {
        $q = trim((string) $request->query('q'));
        $results = collect();
        if (mb_strlen($q) >= 2) {
            $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $q).'%';
            $results = collect([
                __('Restaurants') => Restaurant::published()->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('cuisine', 'like', $like)->orWhere('city', 'like', $like)->orWhere('district', 'like', $like))->take(12)->get()
                    ->map(fn ($r) => ['url' => route('restaurants.show', $r), 'title' => $r->name, 'sub' => $r->cuisine.' · '.$r->city, 'img' => $r->coverUrl()]),
                __('Artistes, troupes et artisans') => Talent::published()->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('category', 'like', $like)->orWhere('tags', 'like', $like)->orWhere('city', 'like', $like))->take(12)->get()
                    ->map(fn ($t) => ['url' => $t->url(), 'title' => $t->name, 'sub' => __($t->meta('label')).' · '.$t->category.' · '.$t->city, 'img' => $t->coverUrl()]),
                __('Événements') => Event::published()->upcoming()->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('title_en', 'like', $like)->orWhere('city', 'like', $like))->take(8)->get()
                    ->map(fn ($e) => ['url' => route('events.show', $e), 'title' => $e->tr('title'), 'sub' => date_human($e->starts_at, 'D MMM YYYY').' · '.$e->city, 'img' => $e->coverUrl()]),
                __('Encyclopédie') => Dish::published()->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('origin_country', 'like', $like))->take(8)->get()
                    ->map(fn ($d) => ['url' => route('dishes.show', $d), 'title' => $d->name, 'sub' => $d->origin_country, 'img' => $d->imageUrl()]),
                __('Actualités') => Article::published()->where(fn ($w) => $w->where('title', 'like', $like)->orWhere('title_en', 'like', $like))->take(6)->get()
                    ->map(fn ($a) => ['url' => route('articles.show', $a), 'title' => $a->tr('title'), 'sub' => date_human($a->published_at, 'D MMM YYYY'), 'img' => $a->coverUrl()]),
            ])->filter(fn ($c) => $c->isNotEmpty());
        }

        if ($request->wantsJson()) {
            return response()->json($results->map(fn ($items, $group) => ['group' => $group, 'items' => $items->values()])->values());
        }

        seo()->title($q ? __('Recherche : :q', ['q' => $q]) : __('Recherche'))->noindex();

        return view('pages.search', compact('q', 'results'));
    }

    public function placeholder(string $visual, string $seed)
    {
        $svg = Cache::remember("ph:$visual:$seed", 86400 * 30, fn () => (new Placeholder($visual, $seed))->svg());

        return response($svg, 200, ['Content-Type' => 'image/svg+xml', 'Cache-Control' => 'public, max-age=2592000, immutable']);
    }

    public function page(string $page)
    {
        seo()->title(['mentions-legales' => __('Mentions légales'), 'confidentialite' => __('Politique de confidentialité'), 'conditions' => __('Conditions d\'utilisation'), 'a-propos' => __('À propos')][$page]);
        if ($page !== 'a-propos') {
            seo()->noindex();
        }

        return view('pages.static', ['page' => $page]);
    }
}

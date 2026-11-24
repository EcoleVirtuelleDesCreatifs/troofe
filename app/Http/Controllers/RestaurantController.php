<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use App\Support\SchemaOrg;
use App\Support\Faq;
use Illuminate\Http\Request;

class RestaurantController extends Controller
{
    public function index(Request $request)
    {
        $f = $request->validate(['q' => 'nullable|string|max:80', 'city' => 'nullable|string|max:80', 'cuisine' => 'nullable|string|max:80', 'price' => 'nullable|integer|between:1,4', 'delivery' => 'nullable|boolean', 'service' => 'nullable|in:livraison,retrait,sur_place', 'open' => 'nullable|boolean', 'sort' => 'nullable|in:pertinence,note,nom,prix']);

        $query = Restaurant::published()->withAvg(['reviews as rating' => fn ($q) => $q->where('status', 'published')], 'rating')->withCount(['reviews as reviews_count' => fn ($q) => $q->where('status', 'published')]);
        if ($q = $f['q'] ?? null) {
            $query->where(fn ($w) => $w->where('name', 'like', "%$q%")->orWhere('cuisine', 'like', "%$q%")->orWhere('district', 'like', "%$q%")->orWhere('description', 'like', "%$q%"));
        }
        foreach (['city', 'cuisine'] as $k) {
            if (! empty($f[$k])) {
                $query->where($k, $f[$k]);
            }
        }
        if (! empty($f['price'])) {
            $query->where('price_level', '<=', $f['price']);
        }
        if (! empty($f['delivery'])) {
            $query->where('delivery', true);
        }
        if (($f['service'] ?? '') === 'livraison') {
            $query->where('delivery', true);
        } elseif (($f['service'] ?? '') === 'retrait') {
            $query->where('accepts_orders', true);
        }
        match ($f['sort'] ?? 'pertinence') {
            'note' => $query->orderByDesc('rating'),
            'nom' => $query->orderBy('name'),
            'prix' => $query->orderBy('price_level'),
            default => $query->orderByDesc('is_featured')->orderByDesc('is_verified')->orderByDesc('views'),
        };

        if (! empty($f['open'])) {
            $filtered = $query->get()->filter->isOpenNow()->values();
            $page = new \Illuminate\Pagination\LengthAwarePaginator(
                $filtered->forPage($request->integer('page', 1), 12)->values(),
                $filtered->count(), 12, $request->integer('page', 1),
                ['path' => $request->url(), 'query' => $request->query()]
            );
        } else {
            $page = $query->paginate(12)->withQueryString();
        }
        $city = $f['city'] ?? null;
        seo()->title($city ? __('Restaurants africains à :city', ['city' => $city]) : __('Restaurants africains : annuaire et commande en ligne'))
            ->description(__(':n restaurants africains :where : cuisine ivoirienne, sénégalaise, ghanéenne… Avis, carte, horaires et commande en ligne avec paiement Mobile Money.', ['n' => $page->total(), 'where' => $city ? __('à :city', ['city' => $city]) : __('en Afrique de l\'Ouest')]))
            ->schema(SchemaOrg::breadcrumbs([__('Restaurants') => null]))
            ->schema(SchemaOrg::itemList($page->getCollection(), fn ($r) => route('restaurants.show', $r), fn ($r) => $r->name));

        return view('pages.restaurants.index', [
            'restaurants' => $page,
            'cities' => Restaurant::published()->distinct()->orderBy('city')->pluck('city'),
            'cuisines' => Restaurant::published()->distinct()->orderBy('cuisine')->pluck('cuisine'),
            'filters' => $f,
        ]);
    }

    public function show(Restaurant $restaurant)
    {
        abort_unless($restaurant->isPublished() || auth()->user()?->isAdmin() || auth()->id() === $restaurant->owner_id, 404);
        $restaurant->increment('views');
        $restaurant->load(['menuItems' => fn ($q) => $q->where('is_available', true), 'dishes', 'craftsmen' => fn ($q) => $q->published()]);

        seo()->title($restaurant->tr('seo_title') ?: __(':name — restaurant :cuisine à :city', ['name' => $restaurant->name, 'cuisine' => mb_strtolower(__($restaurant->cuisine)), 'city' => $restaurant->city]))
            ->description($restaurant->tr('seo_description') ?: ($restaurant->tr('description') ?: $restaurant->tr('tagline')))
            ->image($restaurant->coverUrl())->type('restaurant')
            ->schema(SchemaOrg::restaurant($restaurant))
            ->schema(SchemaOrg::breadcrumbs([__('Restaurants') => route('restaurants.index'), $restaurant->city => route('restaurants.index', ['city' => $restaurant->city]), $restaurant->name => null]))
            ->faq(Faq::restaurant($restaurant));
        if (! $restaurant->isPublished()) {
            seo()->noindex();
        }

        return view('pages.restaurants.show', [
            'restaurant' => $restaurant,
            'menu' => $restaurant->menuItems->groupBy('category'),
            'events' => $restaurant->events()->published()->upcoming()->with('ticketTypes')->orderBy('starts_at')->take(3)->get(),
            'reviews' => $restaurant->publishedReviews()->with('user')->paginate(8, ['*'], 'avis'),
            'similar' => Restaurant::published()->where('id', '!=', $restaurant->id)->where(fn ($w) => $w->where('city', $restaurant->city)->orWhere('cuisine', $restaurant->cuisine))->take(6)->get(),
        ]);
    }
}

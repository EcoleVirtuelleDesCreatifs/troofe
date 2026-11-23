<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use Illuminate\Http\Request;

class RestaurantController extends Controller
{
    public function index(Request $request)
    {
        $f = $request->validate(['q' => 'nullable|string|max:80', 'city' => 'nullable|string|max:80', 'cuisine' => 'nullable|string|max:80', 'price' => 'nullable|integer|between:1,4', 'delivery' => 'nullable|boolean', 'sort' => 'nullable|in:pertinence,note,nom']);

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
        match ($f['sort'] ?? 'pertinence') {
            'note' => $query->orderByDesc('rating'),
            'nom' => $query->orderBy('name'),
            default => $query->orderByDesc('is_featured')->orderByDesc('is_verified')->orderByDesc('views'),
        };

        return view('pages.restaurants.index', [
            'restaurants' => $query->paginate(12)->withQueryString(),
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

        return view('pages.restaurants.show', [
            'restaurant' => $restaurant,
            'menu' => $restaurant->menuItems->groupBy('category'),
            'events' => $restaurant->events()->published()->upcoming()->with('ticketTypes')->orderBy('starts_at')->take(3)->get(),
            'reviews' => $restaurant->publishedReviews()->with('user')->paginate(8, ['*'], 'avis'),
            'similar' => Restaurant::published()->where('id', '!=', $restaurant->id)->where(fn ($w) => $w->where('city', $restaurant->city)->orWhere('cuisine', $restaurant->cuisine))->take(6)->get(),
        ]);
    }
}

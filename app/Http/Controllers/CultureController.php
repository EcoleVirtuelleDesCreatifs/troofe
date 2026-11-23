<?php

namespace App\Http\Controllers;

use App\Models\Restaurant;
use App\Models\Talent;
use Illuminate\Http\Request;

class CultureController extends Controller
{
    public function hub()
    {
        $byType = collect(array_keys(Talent::TYPES))->mapWithKeys(fn ($t) => [$t => Talent::published()->ofType($t)->with('offers')->orderByDesc('is_featured')->orderByDesc('views')->get()]);

        return view('culture.hub', [
            'byType' => $byType,
            'cities' => Talent::published()->distinct()->count('city'),
            'restaurants' => Restaurant::published()->with(['menuItems' => fn ($q) => $q->where('category', 'Plats')])->orderBy('name')->get(),
        ]);
    }

    public function index(Request $request, string $type)
    {
        $t = Talent::typeFromRoute($type);
        $f = $request->validate(['q' => 'nullable|string|max:80', 'categorie' => 'nullable|string|max:80', 'ville' => 'nullable|string|max:80', 'occasion' => 'nullable|string|max:60', 'budget' => 'nullable|integer|min:0', 'verifie' => 'nullable|boolean', 'tri' => 'nullable|in:pertinence,note,prix,nom']);

        $query = Talent::published()->ofType($t)->with('offers')
            ->withAvg(['reviews as rating' => fn ($q) => $q->where('status', 'published')], 'rating')
            ->withMin('offers as min_price', 'price');
        if ($q = $f['q'] ?? null) {
            $query->where(fn ($w) => $w->where('name', 'like', "%$q%")->orWhere('tagline', 'like', "%$q%")->orWhere('tags', 'like', "%$q%")
                ->orWhere('repertoire', 'like', "%$q%")->orWhere('materials', 'like', "%$q%")->orWhereHas('offers', fn ($o) => $o->where('name', 'like', "%$q%")));
        }
        if (! empty($f['categorie'])) { $query->where('category', $f['categorie']); }
        if (! empty($f['ville'])) { $query->where('city', $f['ville']); }
        if (! empty($f['occasion'])) { $query->whereJsonContains('audiences', $f['occasion']); }
        if (! empty($f['verifie'])) { $query->where('is_verified', true); }
        if (! empty($f['budget'])) { $query->whereHas('offers', fn ($o) => $o->where('price', '<=', $f['budget'])); }
        match ($f['tri'] ?? 'pertinence') {
            'note' => $query->orderByDesc('rating'),
            'prix' => $query->orderBy('min_price'),
            'nom' => $query->orderBy('name'),
            default => $query->orderByDesc('is_featured')->orderByDesc('is_verified')->orderByDesc('views'),
        };

        $all = Talent::published()->ofType($t);

        return view('culture.index', [
            'type' => $t,
            'meta' => Talent::TYPES[$t],
            'talents' => $query->paginate(12)->withQueryString(),
            'categories' => (clone $all)->distinct()->orderBy('category')->pluck('category'),
            'cities' => (clone $all)->distinct()->orderBy('city')->pluck('city'),
            'occasions' => (clone $all)->pluck('audiences')->flatten()->filter()->unique()->sort()->values(),
            'stats' => ['count' => (clone $all)->count(), 'cities' => (clone $all)->distinct()->count('city'), 'verified' => (clone $all)->where('is_verified', true)->count()],
            'filters' => $f,
        ]);
    }

    public function show(string $type, string $slug)
    {
        $talent = Talent::where('slug', $slug)->firstOrFail();
        if ($talent->meta('route') !== $type) {
            return redirect($talent->url(), 301);
        }
        abort_unless($talent->isPublished() || auth()->user()?->isAdmin() || auth()->id() === $talent->owner_id, 404);
        $talent->increment('views');
        $talent->load('offers', 'restaurants', 'dishes');

        // Calendrier : 5 semaines à partir du lundi courant
        $start = now()->startOfWeek();
        $busy = $talent->busyDates();
        $earliest = $talent->earliestDate();
        $days = collect(range(0, 34))->map(function ($i) use ($start, $busy, $earliest) {
            $d = $start->copy()->addDays($i);
            $k = $d->toDateString();

            return ['date' => $k, 'day' => $d->day, 'month' => $i === 0 || $d->day === 1 ? $d->locale(app()->getLocale())->isoFormat('MMM') : null,
                'state' => $busy->contains($k) ? 'busy' : ($k < now()->toDateString() || $k < $earliest ? 'off' : 'free'), 'today' => $d->isToday()];
        });

        return view('culture.show', [
            'talent' => $talent,
            'days' => $days,
            'events' => $talent->events()->published()->upcoming()->with('ticketTypes')->get(),
            'reviews' => $talent->publishedReviews()->with('user')->get(),
            'similar' => Talent::published()->ofType($talent->type)->where('id', '!=', $talent->id)->where(fn ($w) => $w->where('category', $talent->category)->orWhere('city', $talent->city))->with('offers')->take(6)->get(),
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Models\Dish;
use App\Models\Event;
use App\Models\Restaurant;
use App\Models\Review;
use App\Models\Talent;
use App\Notifications\Engagement\NewReviewReceived;
use App\Notifications\Engagement\ReviewReplied;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class EngagementController extends Controller
{
    private const TYPES = ['restaurant' => Restaurant::class, 'talent' => Talent::class, 'event' => Event::class, 'dish' => Dish::class];

    private function target(string $type, int $id): Model
    {
        abort_unless(isset(self::TYPES[$type]), 404);

        return self::TYPES[$type]::published()->findOrFail($id);
    }

    private function urlOf(Model $m): string
    {
        return match (true) {
            $m instanceof Restaurant => route('restaurants.show', $m),
            $m instanceof Event => route('events.show', $m),
            $m instanceof Dish => route('dishes.show', $m),
            default => $m->url(),
        };
    }

    public function favorite(Request $request, string $type, int $id)
    {
        $m = $this->target($type, $id);
        $fav = $m->favorites()->where('user_id', $request->user()->id)->first();
        $fav ? $fav->delete() : $m->favorites()->create(['user_id' => $request->user()->id]);
        $on = ! $fav;

        return $request->wantsJson() ? response()->json(['on' => $on]) : back()->with('success', $on ? __('Ajouté à vos favoris') : __('Retiré de vos favoris'));
    }

    public function follow(Request $request, string $type, int $id)
    {
        $m = $this->target($type, $id);
        $f = $m->follows()->where('user_id', $request->user()->id)->first();
        $f ? $f->delete() : $m->follows()->create(['user_id' => $request->user()->id]);
        $on = ! $f;
        $msg = $on ? __('Vous suivez :name : vous serez prévenu de ses nouveautés.', ['name' => $m->name ?? $m->title]) : __('Vous ne suivez plus :name.', ['name' => $m->name ?? $m->title]);

        return $request->wantsJson() ? response()->json(['on' => $on, 'message' => $msg]) : back()->with('success', $msg);
    }

    public function review(Request $request, string $type, int $id)
    {
        $m = $this->target($type, $id);
        $data = $request->validate(['rating' => 'required|integer|between:1,5', 'body' => 'required|string|min:10|max:2000']);
        abort_if(($m->owner_id ?? $m->organizer_id ?? null) === $request->user()->id, 403, __('Vous ne pouvez pas noter votre propre fiche.'));
        if ($m->reviews()->where('user_id', $request->user()->id)->exists()) {
            return back()->withErrors(['body' => __('Vous avez déjà donné votre avis.')]);
        }
        $review = $m->reviews()->create($data + ['user_id' => $request->user()->id, 'status' => 'published']);
        $owner = $m->owner ?? $m->organizer ?? null;
        $owner?->notify(new NewReviewReceived($review->load('user'), $m->name ?? $m->title, $this->urlOf($m)));

        return redirect($this->urlOf($m).'#avis')->with('success', __('Merci pour votre avis !'));
    }

    public function reply(Request $request, Review $review)
    {
        $m = $review->reviewable;
        abort_unless($request->user()->isAdmin() || ($m->owner_id ?? $m->organizer_id ?? null) === $request->user()->id, 403);
        $review->update($request->validate(['reply' => 'required|string|max:1500']) + ['replied_at' => now()]);
        $review->user->notify(new ReviewReplied($review, $m->name ?? $m->title, $this->urlOf($m)));

        return back()->with('success', __('Réponse publiée.'));
    }
}

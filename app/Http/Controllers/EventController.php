<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;

class EventController extends Controller
{
    public function index(Request $request)
    {
        $query = Event::published()->upcoming()->with('ticketTypes')->orderBy('starts_at');
        if ($c = $request->query('ville')) {
            $query->where('city', $c);
        }
        if ($c = $request->query('categorie')) {
            $query->where('category', $c);
        }
        if ($request->query('quand') === 'semaine') {
            $query->where('starts_at', '<=', now()->endOfWeek());
        } elseif ($request->query('quand') === 'mois') {
            $query->where('starts_at', '<=', now()->endOfMonth());
        }

        return view('pages.events.index', [
            'events' => $query->paginate(12)->withQueryString(),
            'cities' => Event::published()->upcoming()->distinct()->orderBy('city')->pluck('city'),
            'categories' => Event::published()->upcoming()->distinct()->pluck('category'),
        ]);
    }

    public function show(Event $event)
    {
        abort_unless($event->isPublished() || auth()->user()?->isAdmin() || auth()->id() === $event->organizer_id, 404);
        $event->increment('views');
        $event->load('ticketTypes', 'restaurant', 'organizer', 'performers');

        return view('pages.events.show', [
            'event' => $event,
            'performers' => $event->performers->where('status', 'published'),
            'reviews' => $event->publishedReviews()->with('user')->take(10)->get(),
            'more' => Event::published()->upcoming()->where('id', '!=', $event->id)->with('ticketTypes')->orderBy('starts_at')->take(3)->get(),
        ]);
    }
}

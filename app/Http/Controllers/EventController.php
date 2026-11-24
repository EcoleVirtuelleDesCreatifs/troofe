<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Support\SchemaOrg;
use App\Support\Faq;
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

        $list = $query->paginate(12)->withQueryString();
        seo()->title(__('Agenda des événements culinaires et culturels'))
            ->description(__('Festivals, dégustations, dîners-concerts, ateliers et marchés à venir à Abidjan, Accra, Dakar et Lagos. Billets en ligne avec QR code.'))
            ->schema(SchemaOrg::breadcrumbs([__('Événements') => null]))
            ->schema(SchemaOrg::itemList($list->getCollection(), fn ($e) => route('events.show', $e), fn ($e) => $e->tr('title')));
        foreach ($list->getCollection()->take(6) as $e) {
            seo()->schema(SchemaOrg::event($e->loadMissing('organizer', 'performers')));
        }

        $monthEvents = collect(); $calMonth = now()->startOfMonth();
        if ($request->query('view') === 'calendrier') {
            try { $calMonth = \Carbon\Carbon::createFromFormat('Y-m', $request->query('mois', now()->format('Y-m')))->startOfMonth(); } catch (\Throwable) {}
            $monthEvents = Event::published()->whereBetween('starts_at', [$calMonth->copy()->startOfMonth(), $calMonth->copy()->endOfMonth()])
                ->when($request->query('ville'), fn ($q, $v) => $q->where('city', $v))
                ->when($request->query('categorie'), fn ($q, $v) => $q->where('category', $v))
                ->orderBy('starts_at')->get()->groupBy(fn ($e) => $e->starts_at->toDateString());
        }

        return view('pages.events.index', [
            'events' => $list,
            'monthEvents' => $monthEvents,
            'calMonth' => $calMonth,
            'cities' => Event::published()->upcoming()->distinct()->orderBy('city')->pluck('city'),
            'categories' => Event::published()->upcoming()->distinct()->pluck('category'),
        ]);
    }

    public function show(Event $event)
    {
        abort_unless($event->isPublished() || auth()->user()?->isAdmin() || auth()->id() === $event->organizer_id, 404);
        $event->increment('views');
        $event->load('ticketTypes', 'restaurant', 'organizer', 'performers');

        seo()->title($event->tr('seo_title') ?: __(':event — :date, :city', ['event' => $event->tr('title'), 'date' => date_human($event->starts_at, 'D MMM YYYY'), 'city' => $event->city]))
            ->description($event->tr('seo_description') ?: $event->tr('excerpt'))
            ->image($event->coverUrl())->type('event')
            ->schema(SchemaOrg::event($event))
            ->schema(SchemaOrg::breadcrumbs([__('Événements') => route('events.index'), $event->tr('title') => null]))
            ->faq(Faq::event($event));
        if (! $event->isPublished() || $event->starts_at->isPast()) {
            seo()->noindex();
        }

        return view('pages.events.show', [
            'event' => $event,
            'performers' => $event->performers->where('status', 'published'),
            'reviews' => $event->publishedReviews()->with('user')->take(10)->get(),
            'more' => Event::published()->upcoming()->where('id', '!=', $event->id)->with('ticketTypes')->orderBy('starts_at')->take(3)->get(),
        ]);
    }
}

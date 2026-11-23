# Trôofê

> Trôofê ({{ url('/') }}) est la plateforme de référence de la gastronomie, de la culture et des savoir-faire d'Afrique de l'Ouest : annuaire de restaurants africains avec commande en ligne, encyclopédie des plats, agenda et billetterie d'événements culinaires, et réservation de groupes de danse, d'artistes et d'artisans. Basée à Abidjan (Côte d'Ivoire), elle couvre aussi Accra, Dakar, Lagos, Cotonou et Douala. Paiement par Mobile Money (Orange Money, MTN MoMo, Moov, Wave) et carte. Site bilingue français / anglais (ajouter ?lang=en à toute URL).

Informations à jour au {{ now()->locale('fr')->isoFormat('D MMMM YYYY') }}. Prix en francs CFA (FCFA / XOF).

## Rubriques principales

- [Restaurants]({{ route('restaurants.index') }}) : {{ $restaurants->count() }} restaurants africains, carte, horaires, avis et commande en ligne
- [Encyclopédie des plats]({{ route('dishes.index') }}) : {{ $dishes->count() }} plats expliqués (origine, ingrédients, recette)
- [Agenda]({{ route('events.index') }}) : {{ $events->count() }} événements à venir, billets avec QR code
- [Groupes de danse]({{ route('culture.index', 'danse') }}) : troupes traditionnelles, de masques, urbaines et contemporaines à réserver
- [Prestations artistiques]({{ route('culture.index', 'prestations') }}) : griots, kora, balafon, DJ, conteurs, orchestres, peinture en direct
- [Artisans]({{ route('culture.index', 'artisans') }}) : tissage, poterie, sculpture, vannerie, bijoux, calebasses, commande directe aux ateliers
- [Actualités]({{ route('articles.index') }}) : portraits de chefs, tendances, recettes
- [Formules pro]({{ route('pricing') }}) : publication des restaurants, artistes et organisateurs

## Restaurants

@foreach ($restaurants as $r)
- [{{ $r->name }}]({{ route('restaurants.show', $r) }}) — cuisine {{ mb_strtolower($r->cuisine) }}, {{ $r->district ? $r->district.', ' : '' }}{{ $r->city }}@if ($r->ratingCount()) — note {{ number_format($r->ratingAverage(), 1, ',', '') }}/5 ({{ $r->ratingCount() }} avis)@endif @if ($r->menuItems->isNotEmpty()) — plats de {{ money($r->menuItems->min('price')) }} à {{ money($r->menuItems->max('price')) }}@endif @if ($r->accepts_orders) — commande en ligne{{ $r->delivery ? ' et livraison' : ' à emporter' }}@endif
@if ($full)
  - Adresse : {{ collect([$r->address, $r->district, $r->city, $r->country])->filter()->implode(', ') }}@if ($r->phone) · Tél. {{ $r->phone }}@endif

  - {{ \Illuminate\Support\Str::limit(strip_tags($r->description), 300) }}
@foreach ($r->opening_hours ?? [] as $d => $h)
  - {{ $d }} : {{ $h }}
@endforeach
@foreach ($r->menuItems->take(12) as $m)
  - {{ $m->name }} : {{ money($m->price) }}
@endforeach
@endif
@endforeach

@foreach (\App\Models\Talent::TYPES as $type => $meta)
## {{ $meta['plural'] }}

@foreach ($talents[$type] ?? [] as $t)
- [{{ $t->name }}]({{ $t->url() }}) — {{ $t->category }}, {{ $t->city }} ({{ $t->country }}) — {{ $t->tagline }}@if ($t->offers->isNotEmpty()) — à partir de {{ money($t->offers->min('price')) }}@endif{{ $t->is_verified ? ' — profil vérifié' : '' }}
@if ($full)
  - {{ \Illuminate\Support\Str::limit(strip_tags($t->description), 300) }}
@foreach ($t->offers as $o)
  - {{ $o->name }}{{ $o->duration ? ' ('.$o->duration.')' : '' }}{{ $o->lead_time ? ' (délai '.$o->lead_time.')' : '' }} : {{ money($o->price) }}
@endforeach
@if ($t->zones)  - Zone d'intervention : {{ implode(', ', $t->zones) }}
@endif
@if (! $t->isCraft())  - Réservation au moins {{ $t->min_notice_days }} jours à l'avance
@endif
@endif
@endforeach

@endforeach
## Événements à venir

@foreach ($events as $e)
- [{{ $e->title }}]({{ route('events.show', $e) }}) — {{ $e->starts_at->locale('fr')->isoFormat('dddd D MMMM YYYY [à] HH:mm') }}, {{ $e->venue }}, {{ $e->city }}@if ($e->ticketTypes->isNotEmpty()) — billets de {{ money($e->ticketTypes->min('price')) }} à {{ money($e->ticketTypes->max('price')) }}@else — entrée libre @endif

@endforeach

## Encyclopédie des plats

@foreach ($dishes as $d)
- [{{ $d->name }}]({{ route('dishes.show', $d) }}) — {{ $d->origin_country }} — {{ \Illuminate\Support\Str::limit(strip_tags($d->summary), $full ? 400 : 160) }}
@if ($full && $d->ingredients)  - Ingrédients : {{ implode(', ', $d->ingredients) }}
@endif
@endforeach

## Articles récents

@foreach ($articles as $a)
- [{{ $a->title }}]({{ route('articles.show', $a) }}) ({{ $a->published_at->toDateString() }}) — {{ \Illuminate\Support\Str::limit($a->excerpt, 160) }}
@endforeach

## Optional

- [Version détaillée de ce fichier]({{ route('llms.full') }})
- [Plan du site]({{ route('sitemap') }})
- [Flux RSS]({{ route('feed') }})
- [Contact]({{ route('contact') }}) : {{ config('troofe.admin_email') }}

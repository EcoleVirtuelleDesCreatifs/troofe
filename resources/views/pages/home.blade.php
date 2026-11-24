@extends('layouts.app', ['description' => __('Trôofê, la maison numérique de la gastronomie africaine : restaurants, encyclopédie culinaire, agenda, commandes et espaces publicitaires.')])
@section('content')
@php
  $ev = $events->first(); $top = $restaurants->first();
  $avg = $avgRating ? number_format($avgRating, 1, ',', '') : '—';
  $dishPhotos = ['alloco' => 'alloco.jpg', 'attieke-poisson-braise' => 'attieke.jpg', 'kedjenou' => 'kedjenou.jpg', 'jollof-rice' => 'jollof.jpg'];
  $articlePhotos = ['nuit-des-saveurs-vingt-restaurants-reunis-a-treichville' => 'table-africaine.jpg', 'tantie-rose-trente-ans-de-braise-a-angre' => 'cuisine.jpg', 'jollof-qui-a-la-meilleure-recette' => 'jollof.jpg'];
  $slides = [
    ['k' => __('Plateforme'), 'e' => __('La plateforme de la gastronomie africaine'), 't' => [__('La table africaine,'), '<em>'.__('en un seul lieu.').'</em>'],
     'l' => __('Restaurants partenaires, encyclopédie des plats, agenda des événements et espaces publicitaires pour les marques.'),
     'c' => [[route('restaurants.index'), __('Découvrir les restaurants')], [route('advertise'), __('Annoncer sur Trôofê')]],
     'img' => asset('assets/img/photos/galerie-8k2a8873-1280.webp'), 'b' => [$avg.'/5', __('note moyenne des avis')]],
    ['k' => __('Restaurants'), 'e' => __('Annuaire vérifié'), 't' => [__('Les bonnes adresses,'), '<em>'.__('vérifiées.').'</em>'],
     'l' => __(':count restaurants partenaires à Abidjan, Accra, Dakar, Lagos et Cotonou. Menus, horaires et commande en ligne.', ['count' => $counts['restaurants']]),
     'c' => [[route('restaurants.index'), __('Voir l\'annuaire')], [route('partner'), __('Inscrire mon restaurant')]],
     'img' => $top?->cover ? $top->coverUrl() : asset('assets/img/home/restaurant.jpg'), 'b' => [$counts['restaurants'], __('restaurants partenaires')]],
    ['k' => __('Encyclopédie'), 'e' => __('Encyclopédie culinaire'), 't' => [__('Chaque plat'), '<em>'.__('a une histoire.').'</em>'],
     'l' => __('Origines, ingrédients, préparation, et les restaurants où les goûter. Les origines partagées sont présentées avec nuance.'),
     'c' => [[route('dishes.index'), __('Ouvrir l\'encyclopédie')], [$dishes->isNotEmpty() ? route('dishes.show', $dishes->first()) : route('dishes.index'), __('Lire une fiche')]],
     'img' => asset('assets/img/home/jollof.jpg'), 'b' => [$counts['dishes'], __('plats documentés')]],
    ['k' => __('Événements'), 'e' => __('Agenda'), 't' => [__('Des rendez-vous'), '<em>'.__('toute l\'année.').'</em>'],
     'l' => $ev ? __('Prochain : :title, le :date à :city.', ['title' => $ev->tr('title'), 'date' => $ev->starts_at->locale(app()->getLocale())->isoFormat('D MMMM'), 'city' => $ev->city]) : __('Dégustations, ateliers et soirées.'),
     'c' => [[route('events.index'), __('Voir l\'agenda')], [$ev ? route('events.show', $ev) : route('events.index'), __('Réserver')]],
     'img' => $ev?->cover ? $ev->coverUrl() : asset('assets/img/photos/galerie-8k2a9994-1280.webp'), 'b' => [$counts['events'], __('événements à venir')]],
    ['k' => __('Publicité'), 'e' => __('Régie publicitaire'), 't' => [__('Votre marque,'), '<em>'.__('au bon endroit.').'</em>'],
     'l' => __(':count espaces réservables en ligne, avec statistiques en temps réel.', ['count' => $adStats['slots']]),
     'c' => [[route('advertise'), __('Voir les espaces')], [route('advertise').'#home_hero', __('Réserver cet en-tête')]],
     'img' => asset('assets/img/photos/galerie-8k2a8916-1280.webp'), 'b' => [$adStats['slots'], __('espaces publicitaires')]],
  ];
  if ($heroAd) {
    $adImg = $heroAd->image ? \Illuminate\Support\Facades\Storage::disk('public')->url($heroAd->image) : media_url(null, 'article', 'ad'.$heroAd->id);
    $heroAd->increment('impressions');
    array_splice($slides, 2, 0, [[
      'k' => __('Partenaire'), 'e' => __('Sponsorisé').' · '.$heroAd->advertiser, 't' => [$heroAd->title], 'l' => $heroAd->text,
      'c' => [[route('ads.click', $heroAd), $heroAd->cta]], 'img' => $adImg, 'b' => [__('Partenaire'), $heroAd->advertiser], 'ad' => true,
    ]]);
  }
@endphp
<div class="home-flow" data-home-motion>
<div class="home-scroll-progress" aria-hidden="true"><i></i></div>
<section class="hs2 home-photos" aria-roledescription="carrousel" aria-label="{{ __('À la une') }}" tabindex="0">
  <div class="wrap gutter hs2-stage">
    @foreach ($slides as $i => $x)
      <div class="hs2-slide{{ $i === 0 ? ' on' : '' }}" data-label="{{ $x['k'] }}" aria-hidden="{{ $i !== 0 ? 'true' : 'false' }}">
        <div class="hs2-copy"><div class="eyebrow hs2-a">{{ $x['e'] }}</div><{{ $i === 0 ? 'h1' : 'h2' }} class="hs2-title">@foreach ($x['t'] as $j => $ln)<span class="ln"><span style="--d:{{ $j }}">{!! $ln !!}</span></span>@endforeach</{{ $i === 0 ? 'h1' : 'h2' }}><p class="lead hs2-a" style="--d:2">{{ $x['l'] }}</p>
          <div class="d-flex gap-2 flex-wrap hs2-a" style="--d:3">@foreach ($x['c'] as $j => [$h, $l])<a href="{{ $h }}" class="btn {{ $j ? 'btn-line-light' : 'btn-ocre' }}" @if (! empty($x['ad']))rel="noopener sponsored nofollow"@endif>{{ $l }}</a>@endforeach</div></div>
        <div class="hs2-art" aria-hidden="true"><span class="ring r2"></span><span class="ring"></span><div class="disc"><img src="{{ $x['img'] }}" @if ($srcset = \App\Support\PhotoLibrary::srcset($x['img']))srcset="{{ $srcset }}" sizes="(max-width:900px) 260px, 600px"@endif alt="" width="700" height="700" loading="{{ $i === 0 ? 'eager' : 'lazy' }}" fetchpriority="{{ $i === 0 ? 'high' : 'auto' }}" decoding="async"></div><div class="badge-c"><div><b>{{ $x['b'][0] }}</b><span>{{ $x['b'][1] }}</span></div></div></div>
      </div>
    @endforeach
  </div>
  <div class="wrap gutter hs2-nav"><div class="hs2-dots">
    @foreach ($slides as $i => $x)<button type="button" class="hs2-dot{{ $i === 0 ? ' on' : '' }}" data-i="{{ $i }}"><span class="n">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }}</span><span class="t">{{ $x['k'] }}</span><span class="bar"><i></i></span></button>@endforeach
  </div>
    <div class="hs2-arrows"><button type="button" class="hs2-arrow" data-step="-1" aria-label="{{ __('Précédent') }}"><i class="bi bi-arrow-left"></i></button><button type="button" class="hs2-arrow" data-step="1" aria-label="{{ __('Suivant') }}"><i class="bi bi-arrow-right"></i></button></div>
  </div>
</section>

<div class="wrap gutter ih-search"><form action="{{ route('search') }}" method="get" role="search">
  <label><span>{{ __('Rechercher') }}</span><input name="q" placeholder="{{ __('Un plat, un restaurant, un quartier') }}" autocomplete="off"></label>
  <label><span>{{ __('Dans') }}</span><select name="scope"><option value="restaurants">{{ __('Restaurants') }}</option><option value="plats">{{ __('Plats') }}</option><option value="tout">{{ __('Tout le site') }}</option></select></label>
  <button class="btn btn-brand"><i class="bi bi-search me-1"></i> {{ __('Rechercher') }}</button></form></div>

<section class="section pb-0"><div class="wrap gutter">
  <div class="figures rv"><div><b data-count="{{ $counts['restaurants'] }}">{{ $counts['restaurants'] }}</b><span>{{ __('restaurants partenaires') }}</span></div><div><b data-count="{{ $counts['dishes'] }}">{{ $counts['dishes'] }}</b><span>{{ __('plats documentés') }}</span></div><div><b data-count="{{ $counts['events'] }}">{{ $counts['events'] }}</b><span>{{ __('événements à venir') }}</span></div><div><b data-count="{{ $adStats['slots'] }}">{{ $adStats['slots'] }}</b><span>{{ __('espaces publicitaires') }}</span></div></div>
</div></section>

<section class="section"><div class="wrap gutter">
  <div class="sh"><div><div class="eyebrow">{{ __('Ce que propose Trôofê') }}</div><h2>{{ __('Quatre services, une seule plateforme') }}</h2></div></div>
  <div class="services rv">
    @foreach ([['01','bi-shop',__('Restaurants'),__('Un annuaire vérifié : menus, horaires, services, commande en ligne.'),route('restaurants.index'),__('Voir l\'annuaire')],['02','bi-book',__('Encyclopédie'),__('Origines, ingrédients et préparation des plats, présentés avec nuance.'),route('dishes.index'),__('Explorer les plats')],['03','bi-calendar-event',__('Événements'),__('Dégustations, ateliers et soirées, avec billetterie et contrôle QR.'),route('events.index'),__('Consulter l\'agenda')],['04','bi-palette',__('Culture'),__('Groupes de danse, artistes et artisans : découvrez les savoir-faire africains et réservez une prestation.'),route('culture.hub'),__('Explorer la culture')]] as [$n,$i,$t,$d,$h,$l])
      <a class="svc" href="{{ $h }}"><span class="n">{{ $n }}</span><i class="bi {{ $i }}"></i><h3>{{ $t }}</h3><p>{{ $d }}</p><span class="link-arrow">{{ $l }} <i class="bi bi-arrow-right"></i></span></a>
    @endforeach
  </div>
</div></section>

<section class="section pt-0"><div class="wrap gutter">
  <div class="rail-wrap" data-auto="4800">
    <div class="section-head"><div><div class="eyebrow">{{ __('Annuaire') }}</div><h2>{{ __('Restaurants à la une') }}</h2></div><div class="d-flex gap-2 align-items-center"><a class="link-arrow me-2 d-none d-md-inline-flex" href="{{ route('restaurants.index') }}">{{ __('Tout l\'annuaire') }} <i class="bi bi-arrow-right"></i></a><button class="btn btn-ghost btn-icon" data-rail="-1" aria-label="{{ __('Précédent') }}"><i class="bi bi-arrow-left"></i></button><button class="btn btn-ghost btn-icon" data-rail="1" aria-label="{{ __('Suivant') }}"><i class="bi bi-arrow-right"></i></button></div></div>
    @php $restaurantPhotos = ['restaurant-ouvert.jpg', 'restaurant.jpg', 'restaurant-jardin.jpg', 'jollof.jpg', 'table-africaine.jpg', 'table-restaurant.jpg', 'cuisine.jpg', 'terrasse.jpg']; @endphp
    <div class="rail">@foreach ($restaurants as $r)<div class="rail-item"><x-restaurant-card :r="$r" :fallback-image="asset('assets/img/home/'.$restaurantPhotos[($r->id - 1) % count($restaurantPhotos)])" /></div>@endforeach<div class="rail-item"><x-ad-slot code="home_native" :fallback-image="asset('assets/img/home/cafe.jpg')" /></div></div>
    <div class="rail-progress"><i></i></div>
    <p class="small muted mt-2 mb-0">{{ __('Photos d’ambiance, non contractuelles : elles ne représentent pas les établissements référencés.') }}</p>
  </div>
</div></section>

<div class="wrap gutter rv"><x-ad-slot code="home_leader" :fallback-image="asset('assets/img/home/patisserie.jpg')" /></div>

<section class="section" id="home-plats"><div class="wrap gutter">
  <div class="sh"><div><div class="eyebrow">{{ __('Encyclopédie culinaire') }}</div><h2>{{ __('Plats africains à découvrir') }}</h2><p class="muted mb-0">{{ __('Chaque plat a une histoire.') }}</p></div><a class="link-arrow" href="{{ route('dishes.index') }}">{{ __('Explorer les plats') }} <i class="bi bi-arrow-right"></i></a></div>
  <div class="grid3">@forelse ($dishes as $dish)<x-dish-card :dish="$dish" />@empty<p class="muted">{{ __('De nouvelles découvertes culinaires arrivent bientôt.') }}</p>@endforelse</div>
</div></section>

<section class="section home-culture" id="home-culture"><div class="wrap gutter">
  <div class="home-culture-intro rv">
    <div class="home-culture-photo"><img src="{{ asset('assets/img/photos/galerie-8k2a0148-1280.webp') }}" srcset="{{ \App\Support\PhotoLibrary::srcset(asset('assets/img/photos/galerie-8k2a0148-1280.webp')) }}" sizes="(max-width:767px) 100vw, 50vw" alt="{{ __('Scène de danse traditionnelle, photo d’illustration') }}" loading="lazy" decoding="async" width="1200" height="800"></div>
    <div class="home-culture-copy"><div class="eyebrow">{{ __('Culture & savoir-faire') }}</div><h2>{{ __('Des rencontres qui font vibrer l’Afrique.') }}</h2><p>{{ __('Danse, musique et artisanat : découvrez des talents, partagez leurs histoires et composez des moments uniques.') }}</p>
      <div class="chip-row mb-4">@foreach (\App\Models\Talent::TYPES as $meta)<a class="chip" href="{{ route('culture.index', $meta['route']) }}"><i class="bi {{ $meta['icon'] }}"></i> {{ __($meta['plural']) }}</a>@endforeach</div>
      <a class="btn btn-brand" href="{{ route('culture.hub') }}">{{ __('Découvrir la culture') }} <i class="bi bi-arrow-right"></i></a>
    </div>
  </div>
</div></section>

@foreach (\App\Models\Talent::TYPES as $type => $meta)
<section class="section home-talent-section" id="home-{{ $meta['route'] }}"><div class="wrap gutter">
  <div class="sh"><div><div class="eyebrow">{{ __('Culture & savoir-faire') }}</div><h2>{{ __($meta['plural']) }}</h2><p class="muted mb-0 home-section-lead">{{ __($meta['lead']) }}</p></div><a class="link-arrow" href="{{ route('culture.index', $meta['route']) }}">{{ __('Tout découvrir') }} <i class="bi bi-arrow-right"></i></a></div>
  <div class="grid3">@forelse ($talents[$type] as $talent)<x-talent-card :talent="$talent" />@empty<div class="panel"><p class="muted">{{ __('Les premiers profils de cette rubrique arrivent bientôt.') }}</p><a class="link-arrow" href="{{ route('partner') }}">{{ __('Référencer mon activité') }} <i class="bi bi-arrow-right"></i></a></div>@endforelse</div>
</div></section>
@endforeach

@if ($events->isNotEmpty())
<section class="section pt-0"><div class="wrap gutter">
  <div class="sh"><div><div class="eyebrow">{{ __('Agenda') }}</div><h2>{{ __('Prochains rendez-vous') }}</h2></div><a class="link-arrow" href="{{ route('events.index') }}">{{ __('Tout l\'agenda') }} <i class="bi bi-arrow-right"></i></a></div>
  <div class="agenda rv">
    @foreach ($events->take(3) as $e)
      <a href="{{ route('events.show', $e) }}"><span class="d"><b>{{ $e->starts_at->format('d') }}</b><span>{{ $e->starts_at->locale(app()->getLocale())->isoFormat('MMM') }}</span></span><span class="min-w-0"><span class="eyebrow">{{ __($e->category) }}</span><h3>{{ $e->tr('title') }}</h3><span class="m">{{ $e->starts_at->format('H:i') }} · {{ $e->venue }}, {{ $e->city }}</span></span><span class="p fw-semibold">@if ($e->minPrice() === null){{ __('Entrée libre') }}@elseif ($e->minPrice() === 0){{ __('Gratuit') }}@else{{ __('Dès') }} {{ money($e->minPrice()) }}@endif</span></a>
    @endforeach
  </div>
</div></section>
@endif

<section class="sell"><div class="wrap gutter sell-in"><div class="rv"><div class="eyebrow">{{ __('Régie publicitaire') }}</div><h2>{{ __('Votre marque, au bon endroit.') }}</h2><p class="mt-3 mb-4">{{ __('Accueil, annuaire, fiches restaurants, articles, agenda : choisissez votre emplacement, composez votre annonce et suivez ses résultats en temps réel.') }}</p><div class="d-flex gap-2 flex-wrap"><a href="{{ route('advertise') }}" class="btn btn-ocre">{{ __('Réserver un espace') }}</a><a href="{{ route('partner') }}" class="btn btn-line-light">{{ __('Inscrire mon établissement') }}</a></div></div>
  <div class="sell-facts rv"><div><b>{{ $adStats['slots'] }}</b><span>{{ __('emplacements sur la plateforme') }}</span></div><div><b>{{ number_format($adStats['minPrice'], 0, ',', ' ') }}</b><span>{{ __('FCFA par jour, à partir de') }}</span></div><div><b>-15 %</b><span>{{ __('dès 30 jours de diffusion') }}</span></div><div><b>24 h</b><span>{{ __('pour la validation d\'une annonce') }}</span></div></div></div></section>

@if ($articles->isNotEmpty())
<section class="section"><div class="wrap gutter">
  <div class="sh"><div><div class="eyebrow">{{ __('Actualités') }}</div><h2>{{ __('À lire') }}</h2></div><a class="link-arrow" href="{{ route('articles.index') }}">{{ __('Toutes les actualités') }} <i class="bi bi-arrow-right"></i></a></div>
  <div class="grid3 news rv">@foreach ($articles as $a)<a href="{{ route('articles.show', $a) }}"><img src="{{ $a->cover ? $a->coverUrl() : asset('assets/img/home/'.($articlePhotos[$a->slug] ?? 'cuisine.jpg')) }}" alt="{{ __('Photo d’illustration culinaire') }}" loading="lazy" decoding="async" width="800" height="500"><span class="eyebrow">{{ __($a->category) }} · {{ date_human($a->published_at, 'D MMM YYYY') }}</span><h3>{{ $a->tr('title') }}</h3><p>{{ $a->tr('excerpt') }}</p></a>@endforeach</div>
</div></section>
@endif
<button type="button" class="home-top" data-home-top aria-label="{{ __('Retour en haut de page') }}"><i class="bi bi-arrow-up" aria-hidden="true"></i></button>
</div>
@endsection

@extends('layouts.app', ['description' => __('Restaurants, encyclopédie culinaire, artistes, artisans et agenda de la gastronomie africaine.')])
@section('content')
<section class="hero">
  <div class="wrap gutter inner">
    <div>
      <div class="eyebrow">{{ __('La plateforme de la gastronomie africaine') }}</div>
      <h1 class="mt-2">{{ __('La table africaine,') }}<br><em>{{ __('en un seul lieu.') }}</em></h1>
      <p class="lead mt-3">{{ __('Trouvez un restaurant, commandez, réservez un groupe de danse ou un artiste, découvrez l\'histoire des plats et l\'agenda des événements.') }}</p>
      <form action="{{ route('restaurants.index') }}" class="searchbox mt-4 d-flex gap-2" role="search">
        <input name="q" class="form-control form-control-lg" placeholder="{{ __('Attiéké, thiéboudienne, Cocody…') }}" aria-label="{{ __('Rechercher un restaurant') }}">
        <button class="btn btn-brand btn-lg"><i class="bi bi-search"></i><span class="d-none d-md-inline ms-1">{{ __('Rechercher') }}</span></button>
      </form>
      <div class="ent-stats mt-4 hero-stats">
        <div><b>{{ $counts['restaurants'] }}</b><span>{{ __('restaurants') }}</span></div>
        <div><b>{{ $counts['dishes'] }}</b><span>{{ __('plats racontés') }}</span></div>
        <div><b>{{ $counts['talents'] }}</b><span>{{ __('artistes et artisans') }}</span></div>
        <div><b>{{ $counts['events'] }}</b><span>{{ __('événements à venir') }}</span></div>
      </div>
    </div>
    <div class="hero-art" aria-hidden="true">
      @foreach ($dishes->take(3) as $i => $d)<div class="plate p{{ $i + 1 }}"><img src="{{ $d->imageUrl() }}" alt=""></div>@endforeach
    </div>
  </div>
</section>

<div class="wrap gutter mt-4"><x-ad-slot code="home_leader" /></div>

<section class="section"><div class="wrap gutter">
  <div class="sh"><div><div class="eyebrow">{{ __('Restaurants') }}</div><h2>{{ __('Les adresses du moment') }}</h2></div><a class="link-arrow" href="{{ route('restaurants.index') }}">{{ __('Tout l\'annuaire') }} <i class="bi bi-arrow-right"></i></a></div>
  <div class="grid">@foreach ($restaurants as $r)<x-restaurant-card :r="$r" />@endforeach</div>
</div></section>

<section class="section"><div class="wrap gutter">
  <div class="sh"><div><div class="eyebrow">{{ __('Culture & savoir-faire') }}</div><h2>{{ __('Danse, musique, artisanat : la fête complète.') }}</h2><p>{{ __('Réservez un groupe de danse ou un artiste pour votre événement, commandez aux artisans qui font la table africaine.') }}</p></div><a class="link-arrow" href="{{ route('culture.hub') }}">{{ __('Tout découvrir') }} <i class="bi bi-arrow-right"></i></a></div>
  <div class="cu-home">
    @foreach (\App\Models\Talent::TYPES as $type => $meta)
      @php $list = $talents[$type]; @endphp
      <a class="cu-hcard" href="{{ route('culture.index', $meta['route']) }}">
        <span class="cu-hcard-img"><img src="{{ $list->first()?->coverUrl('home') ?? media_url(null, $type === 'troupe' ? 'danse' : ($type === 'artiste' ? 'balafon' : 'tissage'), $type) }}" alt=""></span>
        <span class="cu-hcard-b"><span class="d-flex justify-content-between align-items-center"><span class="eyebrow"><i class="bi {{ $meta['icon'] }}"></i> {{ __($meta['plural']) }}</span><i class="bi bi-arrow-up-right"></i></span>
          <h3>{{ __($meta['plural']) }}</h3><span class="cu-hnames">{{ $list->pluck('name')->implode(' · ') }}</span></span>
      </a>
    @endforeach
  </div>
</div></section>

@if ($events->isNotEmpty())
<section class="section"><div class="wrap gutter">
  <div class="sh"><div><div class="eyebrow">{{ __('Agenda') }}</div><h2>{{ __('Prochains événements') }}</h2></div><a class="link-arrow" href="{{ route('events.index') }}">{{ __('Tout l\'agenda') }} <i class="bi bi-arrow-right"></i></a></div>
  <div class="grid g3">@foreach ($events as $e)<x-event-card :event="$e" />@endforeach</div>
</div></section>
@endif

<section class="section"><div class="wrap gutter">
  <div class="sh"><div><div class="eyebrow">{{ __('Encyclopédie') }}</div><h2>{{ __('Chaque plat a une histoire') }}</h2></div><a class="link-arrow" href="{{ route('dishes.index') }}">{{ __('Explorer') }} <i class="bi bi-arrow-right"></i></a></div>
  <div class="grid">@foreach ($dishes as $d)<x-dish-card :dish="$d" />@endforeach</div>
</div></section>

@if ($articles->isNotEmpty())
<section class="section"><div class="wrap gutter">
  <div class="sh"><div><div class="eyebrow">{{ __('Actualités') }}</div><h2>{{ __('À lire') }}</h2></div><a class="link-arrow" href="{{ route('articles.index') }}">{{ __('Tous les articles') }} <i class="bi bi-arrow-right"></i></a></div>
  <div class="grid g3">@foreach ($articles as $a)<x-article-card :article="$a" />@endforeach</div>
</div></section>
@endif

<section class="sell"><div class="wrap gutter sell-in">
  <div><div class="eyebrow">{{ __('Professionnels') }}</div><h2>{{ __('Restaurant, artiste, organisateur : faites-vous connaître.') }}</h2>
    <p>{{ __('Fiche vérifiée, commandes et réservations en ligne, paiement Mobile Money, notifications par e-mail et statistiques. Gratuit pour commencer.') }}</p>
    <div class="d-flex gap-2 flex-wrap mt-3"><a href="{{ route('partner') }}" class="btn btn-ocre">{{ __('Devenir partenaire') }}</a><a href="{{ route('pricing') }}" class="btn btn-outline-light">{{ __('Voir les formules') }}</a></div></div>
</div></section>

<section class="section"><div class="wrap gutter">
  <div class="panel cu-pack text-center mx-auto" style="max-width:760px">
    <div class="eyebrow">{{ __('Lettre d\'information') }}</div><h2 class="h3 mt-1">{{ __('Le meilleur de la semaine, chaque jeudi.') }}</h2>
    <p class="muted">{{ __('Nouvelles adresses, agenda, artistes et recettes. Choisissez vos thèmes ; désinscription en un clic.') }}</p>
    <form method="post" action="{{ route('newsletter.subscribe') }}" class="mt-3">@csrf
      <div class="d-flex flex-wrap gap-2 justify-content-center mb-3">@foreach (config('troofe.newsletter_topics') as $k => $l)<label class="chip"><input type="checkbox" name="topics[]" value="{{ $k }}" checked class="form-check-input me-1"> {{ __($l) }}</label>@endforeach</div>
      <div class="d-flex gap-2 mx-auto" style="max-width:480px"><input type="email" name="email" required class="form-control" placeholder="{{ __('votre@email.com') }}" value="{{ auth()->user()?->email }}"><input type="text" name="website" class="d-none" tabindex="-1" autocomplete="off"><button class="btn btn-brand">{{ __('S\'inscrire') }}</button></div>
    </form>
  </div>
</div></section>
@endsection

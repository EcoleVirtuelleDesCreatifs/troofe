@extends('layouts.app', ['title' => __('Culture & savoir-faire')])
@section('content')
@php $first = $byType['troupe']->first(); @endphp
<x-hero-band :img="$first?->coverUrl('hub') ?? media_url(null, 'danse', 'hub')" :eyebrow="__('Culture & savoir-faire')" :title="__('La fête, avec ceux qui la font.')"
  :lead="__('Groupes de danse, artistes et artisans d\'Afrique de l\'Ouest, réservables en ligne. Disponibilités en direct, devis envoyé par l\'artiste, suivi dans votre espace.')"
  :stats="[[$byType['troupe']->count(), __('groupes de danse')], [$byType['artiste']->count(), __('artistes et formations')], [$byType['artisan']->count(), __('ateliers d\'artisans')], [$cities, __('villes couvertes')]]" />
<section class="section"><div class="wrap gutter">
  @include('culture._switch', ['current' => 'hub'])
  <div class="cu-tiles">
    @foreach (\App\Models\Talent::TYPES as $t => $m)
      <a class="cu-tile" href="{{ route('culture.index', $m['route']) }}"><img src="{{ $byType[$t]->first()?->coverUrl('tile') ?? media_url(null, 'danse', $t) }}" alt="">
        <span class="cu-tile-in"><span class="eyebrow">{{ trans_choice(':count fiche|:count fiches', $byType[$t]->count()) }}</span><h2>{{ __($m['plural']) }}</h2><p>{{ __($m['lead']) }}</p><span class="link-arrow">{{ __('Explorer') }} <i class="bi bi-arrow-right"></i></span></span></a>
    @endforeach
  </div>
  <div class="my-5"><x-ad-slot code="culture_leader" /></div>

  <form class="cu-pack panel" id="pack" method="post" action="{{ route('bookings.pack') }}" data-pack>@csrf
    <div class="eyebrow">{{ __('Composer ma soirée') }}</div><h2 class="h3 mt-1 mb-1">{{ __('Un lieu, une danse, un artiste.') }}</h2>
    <p class="muted">{{ __('Choisissez, obtenez une estimation, puis envoyez toutes les demandes en une fois. Chaque prestataire vous répond avec son devis.') }}</p>
    <div class="row g-3 mt-1">
      <div class="col-md-6 col-lg-3"><label class="form-label" for="pk-r">{{ __('Restaurant (repas)') }}</label><select class="form-select" id="pk-r" name="restaurant"><option value="">{{ __('Sans restaurant') }}</option>@foreach ($restaurants as $r)<option value="{{ $r->id }}" data-avg="{{ (int) round($r->menuItems->avg('price')) }}">{{ $r->name }} · {{ $r->city }}</option>@endforeach</select></div>
      @foreach (['troupe' => __('Groupe de danse'), 'artiste' => __('Artiste')] as $t => $label)
        <div class="col-md-6 col-lg-3"><label class="form-label" for="pk-{{ $t }}">{{ $label }}</label><select class="form-select" id="pk-{{ $t }}" name="offers[]"><option value="">{{ __('Aucun') }}</option>
          @foreach ($byType[$t] as $x)<optgroup label="{{ $x->name }}">@foreach ($x->offers as $o)<option value="{{ $o->id }}" data-price="{{ $o->price }}" data-label="{{ $x->name }} — {{ $o->tr('name') }}">{{ $o->tr('name') }} · {{ money($o->price) }}</option>@endforeach</optgroup>@endforeach
        </select></div>
      @endforeach
      <div class="col-6 col-lg-1"><label class="form-label" for="pk-n">{{ __('Invités') }}</label><input type="number" class="form-control" id="pk-n" name="guests" min="1" value="80" required></div>
      <div class="col-6 col-lg-2"><label class="form-label" for="pk-d">{{ __('Date') }}</label><input type="date" class="form-control" id="pk-d" name="event_date" min="{{ now()->addDays(3)->toDateString() }}" value="{{ now()->addDays(30)->toDateString() }}" required></div>
      <div class="col-md-4"><label class="form-label" for="pk-c">{{ __('Ville') }}</label><input class="form-control" id="pk-c" name="city" required value="{{ auth()->user()?->city ?? 'Abidjan' }}"></div>
      <div class="col-md-4"><label class="form-label" for="pk-v">{{ __('Lieu') }}</label><input class="form-control" id="pk-v" name="venue"></div>
      <div class="col-md-4"><label class="form-label" for="pk-p">{{ __('Téléphone') }}</label><input class="form-control" id="pk-p" name="contact_phone" required value="{{ auth()->user()?->phone }}"></div>
    </div>
    <div class="cu-est mt-3"><div class="cu-est-lines" data-pack-lines></div>
      <div class="cu-est-total"><div><span class="small muted d-block">{{ __('Budget indicatif') }}</span><b class="tnum" data-pack-total>0</b><span class="small muted d-block">{{ __('soit') }} <span data-pack-per></span> {{ __('par invité · hors boissons et transport') }}</span></div>
        @auth<button class="btn btn-brand" data-pack-submit disabled><i class="bi bi-send"></i> {{ __('Envoyer les demandes') }}</button>@else<a class="btn btn-brand" href="{{ route('login') }}">{{ __('Se connecter pour envoyer') }}</a>@endauth</div>
    </div>
  </form>

  @foreach (\App\Models\Talent::TYPES as $t => $m)
    @if ($byType[$t]->isNotEmpty())
      <div class="mt-5"><div class="sh"><div><div class="eyebrow">{{ __('À la une') }}</div><h2>{{ __($m['plural']) }}</h2></div><a class="link-arrow" href="{{ route('culture.index', $m['route']) }}">{{ __('Tout voir') }} <i class="bi bi-arrow-right"></i></a></div>
        <div class="grid">@foreach ($byType[$t]->take(4) as $x)<x-talent-card :talent="$x" />@endforeach</div></div>
    @endif
  @endforeach

  <div class="cu-cta mt-5"><div><div class="eyebrow">{{ __('Artistes, troupes, artisans') }}</div><h2 class="h3 mt-1 mb-2">{{ __('Faites-vous réserver sur Trôofê.') }}</h2><p class="mb-0">{{ __('Fiche gratuite, calendrier de disponibilités, demandes de devis et commandes centralisées, notifications par e-mail. Validation par l\'équipe sous 48 h.') }}</p></div><a class="btn btn-ocre" href="{{ route('partner') }}">{{ __('Référencer mon activité') }}</a></div>
</div></section>
@endsection

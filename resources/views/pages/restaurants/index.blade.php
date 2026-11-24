@extends('layouts.app', ['title' => __('Restaurants')])
@section('content')
<section class="section"><div class="wrap gutter">
  @include('partials.crumbs', ['items' => [__('Restaurants') => null]])
  <div class="section-head"><div><h1 class="h2 mb-1">{{ __('Annuaire des restaurants') }}</h1><p class="muted mb-0">{{ __('Horaires et disponibilités déclarés par chaque restaurant.') }}</p></div></div>
  <form class="panel mb-4" method="get"><div class="row g-2 align-items-end">
    <div class="col-lg-4"><label class="form-label" for="rf-q">{{ __('Nom, plat ou quartier') }}</label><input class="form-control" id="rf-q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ __('Ex. : poisson braisé, Plateau') }}"></div>
    <div class="col-6 col-lg-2"><label class="form-label" for="rf-c">{{ __('Cuisine') }}</label><select class="form-select" id="rf-c" name="cuisine"><option value="">{{ __('Toutes') }}</option>@foreach ($cuisines as $c)<option @selected(($filters['cuisine'] ?? '') === $c)>{{ $c }}</option>@endforeach</select></div>
    <div class="col-6 col-lg-2"><label class="form-label" for="rf-v">{{ __('Ville') }}</label><select class="form-select" id="rf-v" name="city"><option value="">{{ __('Toutes') }}</option>@foreach ($cities as $c)<option @selected(($filters['city'] ?? '') === $c)>{{ $c }}</option>@endforeach</select></div>
    <div class="col-6 col-lg-2"><label class="form-label" for="rf-b">{{ __('Budget') }}</label><select class="form-select" id="rf-b" name="price"><option value="">{{ __('Tous') }}</option>@foreach ([1 => '₣ '.__('Économique'), 2 => '₣₣ '.__('Moyen'), 3 => '₣₣₣ '.__('Élevé'), 4 => '₣₣₣₣ '.__('Gastronomique')] as $v => $l)<option value="{{ $v }}" @selected((string) ($filters['price'] ?? '') === (string) $v)>{{ $l }}</option>@endforeach</select></div>
    <div class="col-6 col-lg-2"><label class="form-label" for="rf-s">{{ __('Service') }}</label><select class="form-select" id="rf-s" name="service"><option value="">{{ __('Tous') }}</option><option value="livraison" @selected(($filters['service'] ?? '') === 'livraison')>{{ __('Livraison') }}</option><option value="retrait" @selected(($filters['service'] ?? '') === 'retrait')>{{ __('Retrait') }}</option><option value="sur_place" @selected(($filters['service'] ?? '') === 'sur_place')>{{ __('Sur place') }}</option></select></div>
    <div class="col-12 d-flex flex-wrap gap-3 align-items-center mt-3"><div class="form-check form-switch m-0"><input class="form-check-input" type="checkbox" id="rf-o" name="open" value="1" @checked($filters['open'] ?? false)><label class="form-check-label" for="rf-o">{{ __('Ouvert maintenant') }}</label></div>
      <div class="ms-auto d-flex align-items-center gap-2"><label class="small muted" for="rf-sort">{{ __('Trier') }}</label><select class="form-select form-select-sm" id="rf-sort" name="sort" style="width:auto"><option value="pertinence">{{ __('Pertinence') }}</option><option value="note" @selected(($filters['sort'] ?? '') === 'note')>{{ __('Mieux notés') }}</option><option value="prix" @selected(($filters['sort'] ?? '') === 'prix')>{{ __('Prix croissant') }}</option><option value="nom" @selected(($filters['sort'] ?? '') === 'nom')>{{ __('Nom A→Z') }}</option></select><a href="{{ route('restaurants.index') }}" class="btn btn-ghost btn-sm">{{ __('Réinitialiser') }}</a><button class="btn btn-brand btn-sm">{{ __('Filtrer') }}</button></div></div>
  </div></form>
  <x-ad-slot code="annuaire_leader" />
  <div class="d-flex justify-content-between mb-3 mt-4"><span class="muted small">{{ trans_choice(':count restaurant|:count restaurants', $restaurants->total()) }}{{ $restaurants->contains('is_featured', true) ? ' · '.__('les emplacements sponsorisés sont signalés') : '' }}</span></div>
  @if ($restaurants->isEmpty())
    <x-empty icon="bi-search" :text="__('Aucun restaurant ne correspond. Essayez un autre quartier ou retirez un filtre.')" />
  @else
    <div class="grid">@foreach ($restaurants as $i => $r)<x-restaurant-card :r="$r" />@if ($i === 2)<x-ad-slot code="annuaire_native" />@endif @endforeach</div>
    <div class="mt-4">{{ $restaurants->links() }}</div>
  @endif
</div></section>
@endsection

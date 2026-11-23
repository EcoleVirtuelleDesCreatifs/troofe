@extends('layouts.app', ['title' => __('Restaurants')])
@section('content')
<section class="section"><div class="wrap gutter">
  @include('partials.crumbs', ['items' => [__('Restaurants') => null]])
  <div class="sh"><div><div class="eyebrow">{{ __('Annuaire') }}</div><h1 class="h2">{{ __('Restaurants africains') }}</h1><p>{{ trans_choice(':count adresse|:count adresses', $restaurants->total()) }}</p></div></div>
  <form class="panel mb-4 row g-2 align-items-end" method="get">
    <div class="col-lg-4"><label class="form-label" for="q">{{ __('Nom, plat ou quartier') }}</label><input class="form-control" id="q" name="q" value="{{ $filters['q'] ?? '' }}"></div>
    <div class="col-6 col-lg-2"><label class="form-label" for="city">{{ __('Ville') }}</label><select class="form-select" id="city" name="city"><option value="">{{ __('Toutes') }}</option>@foreach ($cities as $c)<option @selected(($filters['city'] ?? '') === $c)>{{ $c }}</option>@endforeach</select></div>
    <div class="col-6 col-lg-2"><label class="form-label" for="cuisine">{{ __('Cuisine') }}</label><select class="form-select" id="cuisine" name="cuisine"><option value="">{{ __('Toutes') }}</option>@foreach ($cuisines as $c)<option @selected(($filters['cuisine'] ?? '') === $c)>{{ $c }}</option>@endforeach</select></div>
    <div class="col-6 col-lg-2"><label class="form-label" for="sort">{{ __('Trier') }}</label><select class="form-select" id="sort" name="sort"><option value="pertinence">{{ __('Pertinence') }}</option><option value="note" @selected(($filters['sort'] ?? '') === 'note')>{{ __('Mieux notés') }}</option><option value="nom" @selected(($filters['sort'] ?? '') === 'nom')>{{ __('Nom A→Z') }}</option></select></div>
    <div class="col-6 col-lg-2 d-flex gap-2 align-items-center"><div class="form-check form-switch m-0"><input class="form-check-input" type="checkbox" name="delivery" value="1" id="dl" @checked($filters['delivery'] ?? false)><label class="form-check-label small" for="dl">{{ __('Livraison') }}</label></div><button class="btn btn-brand ms-auto">{{ __('Filtrer') }}</button></div>
  </form>
  <x-ad-slot code="annuaire_leader" />
  @if ($restaurants->isEmpty())
    <x-empty icon="bi-search" :text="__('Aucun restaurant ne correspond. Retirez un filtre ou essayez une autre ville.')" />
  @else
    <div class="grid mt-4">@foreach ($restaurants as $i => $r)<x-restaurant-card :r="$r" />@if ($i === 2)<x-ad-slot code="annuaire_native" />@endif @endforeach</div>
    <div class="mt-4">{{ $restaurants->links() }}</div>
  @endif
</div></section>
@endsection

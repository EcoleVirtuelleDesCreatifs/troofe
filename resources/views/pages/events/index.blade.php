@extends('layouts.app', ['title' => __('Agenda')])
@section('content')
<section class="section"><div class="wrap gutter">
  @include('partials.crumbs', ['items' => [__('Événements') => null]])
  <div class="sh"><div><div class="eyebrow">{{ __('Agenda') }}</div><h1 class="h2">{{ __('Événements à venir') }}</h1></div></div>
  <form class="panel mb-4 row g-2 align-items-end" method="get">
    <div class="col-md-3"><label class="form-label" for="quand">{{ __('Quand') }}</label><select class="form-select" id="quand" name="quand"><option value="">{{ __('Toutes les dates') }}</option><option value="semaine" @selected(request('quand') === 'semaine')>{{ __('Cette semaine') }}</option><option value="mois" @selected(request('quand') === 'mois')>{{ __('Ce mois-ci') }}</option></select></div>
    <div class="col-md-3"><label class="form-label" for="ville">{{ __('Ville') }}</label><select class="form-select" id="ville" name="ville"><option value="">{{ __('Toutes') }}</option>@foreach ($cities as $c)<option @selected(request('ville') === $c)>{{ $c }}</option>@endforeach</select></div>
    <div class="col-md-4"><label class="form-label" for="categorie">{{ __('Catégorie') }}</label><select class="form-select" id="categorie" name="categorie"><option value="">{{ __('Toutes') }}</option>@foreach ($categories as $c)<option value="{{ $c }}" @selected(request('categorie') === $c)>{{ __($c) }}</option>@endforeach</select></div>
    <div class="col-md-2"><button class="btn btn-brand w-100">{{ __('Filtrer') }}</button></div>
  </form>
  <x-ad-slot code="events_banner" />
  @if ($events->isEmpty())<x-empty icon="bi-calendar-x" :text="__('Aucun événement à venir pour ces critères.')" />@else
  <div class="grid g3 mt-4">@foreach ($events as $i => $e)<x-event-card :event="$e" />@if ($i === 1)<x-ad-slot code="events_native" />@endif @endforeach</div><div class="mt-4">{{ $events->links() }}</div>@endif
</div></section>
@endsection

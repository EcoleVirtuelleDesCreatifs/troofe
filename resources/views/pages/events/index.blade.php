@extends('layouts.app', ['title' => __('Agenda')])
@section('content')
@php $cal = request('view') === 'calendrier'; $base = array_filter(['ville' => request('ville'), 'categorie' => request('categorie')]); @endphp
<section class="section"><div class="wrap gutter">
  @include('partials.crumbs', ['items' => [__('Événements') => null]])
  <div class="section-head"><div><h1 class="h2 mb-1">{{ __('Agenda') }}</h1><p class="muted mb-0">{{ __('Dégustations, ateliers, concerts et marchés, toute l\'année.') }}</p></div>
    <div class="segmented" role="tablist"><a href="{{ route('events.index', $base) }}" class="{{ $cal ? '' : 'on' }}"><i class="bi bi-list-ul"></i> {{ __('Liste') }}</a><a href="{{ route('events.index', $base + ['view' => 'calendrier']) }}" class="{{ $cal ? 'on' : '' }}"><i class="bi bi-calendar3"></i> {{ __('Calendrier') }}</a></div></div>
  <form class="row g-2 mb-4" method="get">
    @if ($cal)<input type="hidden" name="view" value="calendrier"><input type="hidden" name="mois" value="{{ $calMonth->format('Y-m') }}">@endif
    <div class="col-6 col-md-3"><select class="form-select" name="ville" aria-label="{{ __('Ville') }}"><option value="">{{ __('Toutes les villes') }}</option>@foreach ($cities as $v)<option @selected(request('ville') === $v)>{{ $v }}</option>@endforeach</select></div>
    <div class="col-6 col-md-3"><select class="form-select" name="categorie" aria-label="{{ __('Thème') }}"><option value="">{{ __('Tous les thèmes') }}</option>@foreach ($categories as $c)<option value="{{ $c }}" @selected(request('categorie') === $c)>{{ __($c) }}</option>@endforeach</select></div>
    <div class="col-md-6 text-md-end"><button class="btn btn-ghost">{{ __('Filtrer') }}</button> <a class="btn btn-ghost" href="{{ route('partner') }}"><i class="bi bi-plus-lg"></i> {{ __('Proposer un événement') }}</a></div>
  </form>
  <div class="mb-4"><x-ad-slot code="events_banner" /></div>
  @if ($cal)
    <div class="panel"><div class="d-flex justify-content-between align-items-center mb-3"><a class="btn btn-ghost btn-icon" href="{{ route('events.index', $base + ['view' => 'calendrier', 'mois' => $calMonth->copy()->subMonth()->format('Y-m')]) }}" aria-label="{{ __('Mois précédent') }}"><i class="bi bi-chevron-left"></i></a><h2 class="h5 m-0 text-capitalize">{{ $calMonth->locale(app()->getLocale())->isoFormat('MMMM YYYY') }}</h2><a class="btn btn-ghost btn-icon" href="{{ route('events.index', $base + ['view' => 'calendrier', 'mois' => $calMonth->copy()->addMonth()->format('Y-m')]) }}" aria-label="{{ __('Mois suivant') }}"><i class="bi bi-chevron-right"></i></a></div>
      <div class="calendar">
        @foreach ([__('Lun'),__('Mar'),__('Mer'),__('Jeu'),__('Ven'),__('Sam'),__('Dim')] as $d)<div class="dh">{{ $d }}</div>@endforeach
        @php $start = (int) $calMonth->copy()->startOfMonth()->format('N') - 1; $days = $calMonth->daysInMonth; @endphp
        @for ($i = 0; $i < 42; $i++)
          @php $d = $calMonth->copy()->addDays($i - $start); $inM = $d->month === $calMonth->month; $key = $d->toDateString(); @endphp
          @if ($i >= 35 && ! $inM && ($i - $start) >= $days) @break @endif
          <div class="d {{ $inM ? '' : 'out' }}"><div class="n">{{ $d->day }}</div>@foreach ($monthEvents[$key] ?? [] as $e)<a class="ev" href="{{ route('events.show', $e) }}" title="{{ $e->tr('title') }}">{{ $e->tr('title') }}</a>@endforeach</div>
        @endfor
      </div></div>
  @else
    @if ($events->isEmpty())<x-empty icon="bi-calendar-x" :text="__('Aucun événement à venir pour ces critères.')" />@else
      <div class="grid g3">@foreach ($events as $i => $e)<x-event-card :event="$e" />@if ($i === 1)<x-ad-slot code="events_native" />@endif @endforeach</div><div class="mt-4">{{ $events->links() }}</div>@endif
  @endif
</div></section>
@endsection

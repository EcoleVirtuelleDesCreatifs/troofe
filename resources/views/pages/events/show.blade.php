@extends('layouts.app', ['title' => $event->tr('title'), 'description' => $event->tr('excerpt'), 'ogImage' => $event->coverUrl()])
@section('content')
<x-hero-band :img="$event->coverUrl()" :eyebrow="__($event->category).' · '.$event->city" :title="$event->tr('title')" :lead="$event->tr('excerpt')"
  :stats="[[date_human($event->starts_at, 'D MMM'), date_human($event->starts_at, 'dddd, HH:mm')], [$event->venue, $event->city], [$event->ticketTypes->isEmpty() ? __('Libre') : ($event->minPrice() ? money($event->minPrice()) : __('Gratuit')), __('entrée')]]" />
<section class="section"><div class="wrap gutter">
  @include('partials.crumbs', ['items' => [__('Événements') => route('events.index'), \Illuminate\Support\Str::limit($event->tr('title'), 50) => null]])
  <div class="two-col"><div>
    <div class="d-flex gap-2 flex-wrap mb-4"><x-fav-button type="event" :model="$event" look="btn btn-ghost"> {{ __('Favori') }}</x-fav-button>
      <a class="btn btn-ghost" href="https://calendar.google.com/calendar/render?action=TEMPLATE&text={{ rawurlencode($event->tr('title')) }}&dates={{ $event->starts_at->utc()->format('Ymd\THis\Z') }}/{{ ($event->ends_at ?? $event->starts_at->copy()->addHours(3))->utc()->format('Ymd\THis\Z') }}&location={{ rawurlencode($event->venue.', '.$event->city) }}" target="_blank" rel="noopener"><i class="bi bi-calendar-plus"></i> {{ __('Ajouter à mon agenda') }}</a></div>
    @if ($event->tr('description'))<div class="prose mb-4">{!! $event->tr('description') !!}</div>@endif
    @if ($performers->isNotEmpty())<div class="section-head"><h2 class="h4 mb-0">{{ __('À l\'affiche') }}</h2></div><div class="grid g3 mb-4">@foreach ($performers as $t)<x-talent-card :talent="$t" />@endforeach</div>@endif
    @if ($event->restaurant)<div class="panel mb-4 d-flex gap-3 align-items-center"><img src="{{ $event->restaurant->coverUrl() }}" alt="" style="width:72px;height:72px;border-radius:12px;object-fit:cover"><div><div class="eyebrow">{{ __('En partenariat avec') }}</div><a href="{{ route('restaurants.show', $event->restaurant) }}" class="fw-bold">{{ $event->restaurant->name }}</a></div></div>@endif
  </div>
  <aside class="sticky-side d-grid gap-3">
    @if ($event->ticketTypes->isNotEmpty())
    <form class="panel" method="post" action="{{ route('orders.tickets', $event) }}" data-ticket-form>@csrf
      <h3 class="h5">{{ __('Billetterie') }}</h3>
      @foreach ($event->ticketTypes as $t)
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom gap-2">
          <div><b>{{ $t->name }}</b><div class="small muted">{{ $t->price ? money($t->price) : __('Gratuit') }}@if ($t->remaining() <= 20 && $t->remaining() > 0) · <span class="text-danger">{{ trans_choice('{1} dernière place|[2,*] :count places restantes', $t->remaining()) }}</span>@endif</div></div>
          @if ($t->remaining())<select name="tickets[{{ $t->id }}]" class="form-select form-select-sm" style="width:auto" data-tprice="{{ $t->price }}" aria-label="{{ __('Quantité') }}">@for ($q = 0; $q <= min($t->max_per_order, $t->remaining()); $q++)<option>{{ $q }}</option>@endfor</select>@else<span class="pill bad">{{ __('Complet') }}</span>@endif
        </div>
      @endforeach
      <div class="mt-3" data-holders data-placeholder="{{ __('Nom sur le billet :n (facultatif)') }}"></div>
      <input name="contact_phone" class="form-control form-control-sm mb-2" required placeholder="{{ __('Téléphone') }}" value="{{ auth()->user()?->phone }}">
      <div class="d-flex justify-content-between fw-bold my-2"><span>{{ __('Total') }}</span><span class="tnum" data-ticket-total>—</span></div>
      @auth<button class="btn btn-brand w-100" data-ticket-submit disabled><i class="bi bi-ticket-perforated"></i> {{ __('Réserver mes billets') }}</button>@else<a href="{{ route('login') }}" class="btn btn-brand w-100">{{ __('Se connecter pour réserver') }}</a>@endauth
      <p class="small muted mt-2 mb-0">{{ __('Billets nominatifs avec QR code, envoyés par e-mail. Rappel la veille.') }}</p>
    </form>
    @endif
    <div class="panel"><h3 class="h6">{{ __('Infos pratiques') }}</h3><dl class="kv mb-0"><dt>{{ __('Date') }}</dt><dd>{{ date_human($event->starts_at, 'dddd D MMMM YYYY, HH:mm') }}</dd><dt>{{ __('Lieu') }}</dt><dd>{{ $event->venue }}, {{ $event->city }}</dd>@if ($event->organizer)<dt>{{ __('Organisateur') }}</dt><dd>{{ $event->organizer->name }}</dd>@endif</dl></div>
    <x-ad-slot code="resto_side" />
  </aside></div>
  @if ($more->isNotEmpty())<div class="mt-5"><div class="sh"><h2>{{ __('Autres événements') }}</h2></div><div class="grid g3">@foreach ($more as $e)<x-event-card :event="$e" />@endforeach</div></div>@endif
</div></section>
@endsection

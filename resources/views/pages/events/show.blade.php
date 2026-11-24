@extends('layouts.app', ['title' => $event->tr('title'), 'description' => $event->tr('excerpt'), 'ogImage' => $event->coverUrl()])
@section('content')
<section class="section"><div class="wrap gutter">
  @include('partials.crumbs', ['items' => [__('Événements') => route('events.index'), \Illuminate\Support\Str::limit($event->tr('title'), 50) => null]])
  @unless ($event->isPublished())<div class="notice warn mb-3"><i class="bi bi-hourglass-split"></i><div>{{ __('Aperçu : en attente de validation par l\'équipe Trôofê.') }}</div></div>@endunless
  <div class="detail-hero mb-4"><img src="{{ $event->coverUrl() }}" alt="{{ $event->tr('title') }}"></div>
  <div class="two-col"><div>
    <div class="eyebrow">{{ __($event->category) }}</div><h1 class="mt-1">{{ $event->tr('title') }}</h1>
    <div class="d-flex gap-3 flex-wrap muted mb-3"><span><i class="bi bi-calendar3"></i> {{ date_human($event->starts_at, 'dddd D MMMM YYYY') }}</span><span><i class="bi bi-clock"></i> {{ $event->starts_at->format('H:i') }}@if ($event->ends_at) – {{ $event->ends_at->format('H:i') }}@endif</span><span><i class="bi bi-geo-alt"></i> {{ $event->venue }}, {{ $event->city }}</span></div>
    @if ($event->tr('description'))<div class="prose mb-4">{!! $event->tr('description') !!}</div>@endif
    @if ($performers->isNotEmpty())<div class="section-head"><h2 class="h4 mb-0">{{ __('À l\'affiche') }}</h2></div><div class="grid g3 mb-4">@foreach ($performers as $t)<x-talent-card :talent="$t" />@endforeach</div>@endif
    @if ($event->restaurant)<div class="panel mb-4 d-flex gap-3 align-items-center"><img src="{{ $event->restaurant->coverUrl() }}" alt="" style="width:72px;height:72px;border-radius:12px;object-fit:cover"><div><div class="eyebrow">{{ __('En partenariat avec') }}</div><a href="{{ route('restaurants.show', $event->restaurant) }}" class="fw-bold">{{ $event->restaurant->name }}</a></div></div>@endif
    <div class="panel"><h2 class="h5">{{ __('Lieu') }}</h2><div class="map-ph mb-2"><i class="bi bi-geo-alt-fill pin"></i></div><span class="small">{{ $event->venue }}, {{ $event->city }}</span></div>
    <p class="small muted mt-3">{{ __('Organisé par') }} {{ $event->organizer?->name ?? 'Trôofê' }}</p>
  </div>
  <aside class="sticky-side"><div class="panel" id="buy-box">
    @if ($event->ticketTypes->isEmpty())
      <h2 class="h5">{{ __('Entrée libre') }}</h2><p class="small muted">{{ __('Inscription conseillée pour recevoir les informations pratiques.') }}</p>
      @if (($event->ends_at ?? $event->starts_at)->isPast())<p class="small muted mb-0">{{ __('Événement terminé.') }}</p>@endif
    @else
    <form method="post" action="{{ route('orders.tickets', $event) }}" data-ticket-form>@csrf
      <h2 class="h5">{{ __('Billets') }}</h2>
      @foreach ($event->ticketTypes as $t)
        <div class="d-flex justify-content-between align-items-center py-2 border-bottom gap-2" style="border-color:var(--line)!important">
          <div><b>{{ $t->name }}</b><div class="small muted">{{ $t->price ? money($t->price) : __('Gratuit') }}@if ($t->remaining() <= 20 && $t->remaining() > 0) · <span class="text-danger">{{ trans_choice('{1} dernière place|[2,*] :count places restantes', $t->remaining()) }}</span>@elseif ($t->remaining() === 0) · {{ __('Complet') }}@endif</div></div>
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
  </div></aside></div>
  @if ($more->isNotEmpty())<div class="mt-5"><div class="sh"><h2>{{ __('Autres événements') }}</h2></div><div class="grid g3">@foreach ($more as $e)<x-event-card :event="$e" />@endforeach</div></div>@endif
</div></section>
@endsection

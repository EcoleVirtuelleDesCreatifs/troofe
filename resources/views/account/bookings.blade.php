@extends('account.layout', ['title' => __('Mes réservations'), 'subtitle' => __('Groupes de danse, artistes et commandes aux artisans')])
@section('actions')<a class="btn btn-ghost btn-sm" href="{{ route('culture.hub') }}"><i class="bi bi-plus-lg"></i> {{ __('Nouvelle demande') }}</a>@endsection
@section('account')
@if ($received->isNotEmpty())<div class="notice info mb-3"><i class="bi bi-inbox"></i><div>{{ trans_choice('Vous avez :count demande reçue pour vos fiches.|Vous avez :count demandes reçues pour vos fiches.', $received->count()) }} <a href="{{ url('/pro/bookings') }}">{{ __('Les gérer dans l\'espace pro') }}</a></div></div>@endif
@forelse ($mine as $b)
  <div class="panel cu-res mb-3"><img src="{{ $b->talent->coverUrl() }}" alt="">
    <div class="min-w-0 flex-grow-1">
      <div class="d-flex justify-content-between gap-2 flex-wrap align-items-start"><div><div class="eyebrow">{{ __($b->kindLabel()) }} · {{ __($b->talent->meta('label')) }}</div><a href="{{ $b->talent->url() }}" class="fw-bold">{{ $b->talent->name }}</a></div>{!! status_pill($b->status, $b->statusLabel()) !!}</div>
      <div class="small muted mt-1">{{ $b->reference }}@if ($b->offer) · {{ $b->offer->tr('name') }}@endif @if ($b->quantity && $b->kind === 'commande') · ×{{ $b->quantity }}@endif @if ($b->event_date) · <b style="color:var(--ink)">{{ date_human($b->event_date) }}</b>@endif @if ($b->venue) · {{ $b->venue }}@endif</div>
      @if ($b->message)<p class="small mb-0 mt-2 cu-msg">{{ $b->message }}</p>@endif
      @if ($b->quote_note)<p class="small mb-0 mt-2"><i class="bi bi-reply"></i> <i>{{ $b->quote_note }}</i></p>@endif
      <div class="d-flex justify-content-between align-items-center gap-2 flex-wrap mt-3">
        <span class="tnum">@if ($b->amount)<b>{{ money($b->amount) }}</b> <span class="small muted">{{ $b->status === 'devis_envoye' ? __('devis') : __('montant') }}</span>@else<span class="small muted">{{ __('En attente de devis') }}</span>@endif</span>
        <div class="d-flex gap-2">
          @if ($b->status === 'devis_envoye')<form method="post" action="{{ route('bookings.transition', [$b, 'confirmee']) }}">@csrf<button class="btn btn-brand btn-sm">{{ __('Accepter le devis') }}</button></form>@endif
          @if ($b->isOpen())<form method="post" action="{{ route('bookings.transition', [$b, 'annulee']) }}" onsubmit="return confirm(@js(__('Annuler cette demande ?')))">@csrf<button class="btn btn-ghost btn-sm">{{ __('Annuler') }}</button></form>@endif
          @if ($b->status === 'realisee')<a class="btn btn-soft btn-sm" href="{{ $b->talent->url() }}#avis">{{ __('Donner mon avis') }}</a>@endif
        </div>
      </div>
    </div>
  </div>
@empty
  <x-empty icon="bi-calendar2-check" :text="__('Aucune réservation pour le moment.')"><a class="btn btn-brand" href="{{ route('culture.hub') }}">{{ __('Découvrir les artistes') }}</a></x-empty>
@endforelse
@endsection

@extends('account.layout', ['title' => __('Mes billets'), 'subtitle' => __('Présentez le QR code à l\'entrée, sur téléphone ou imprimé.')])
@section('account')
@forelse ($upcoming as $t)
  <div class="ticket-card mb-3 {{ $t->checked_in_at ? 'used' : '' }}">
    <div><div class="eyebrow">{{ $t->type->name }}</div><h2 class="h5 mb-1">{{ $t->type->event->tr('title') }}</h2>
      <div class="small">{{ date_human($t->type->event->starts_at, 'dddd D MMMM YYYY, HH:mm') }}<br>{{ $t->type->event->venue }}, {{ $t->type->event->city }}</div>
      <div class="small mt-2">{{ __('Porteur') }} : <b>{{ $t->holder_name }}</b> · <span class="mono">{{ strtoupper(substr($t->code, 0, 8)) }}</span></div>
      @if ($t->checked_in_at)<span class="pill info mt-2">{{ __('Utilisé le :d', ['d' => date_human($t->checked_in_at, 'D MMM, HH:mm')]) }}</span>@endif
      <a href="{{ route('tickets.show', $t->code) }}" class="btn btn-ghost btn-sm mt-2"><i class="bi bi-arrows-fullscreen"></i> {{ __('Plein écran') }}</a></div>
    <div>{!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(140)->margin(0)->generate($t->code) !!}</div>
  </div>
@empty
  <x-empty icon="bi-ticket-perforated" :text="__('Aucun billet à venir.')"><a href="{{ route('events.index') }}" class="btn btn-brand">{{ __('Voir l\'agenda') }}</a></x-empty>
@endforelse
@if ($past->isNotEmpty())<h2 class="h6 mt-4 muted">{{ __('Événements passés') }}</h2>@foreach ($past as $t)<div class="d-flex justify-content-between small py-2 border-bottom"><span>{{ $t->type->event->tr('title') }} · {{ $t->type->name }}</span><span class="muted">{{ date_human($t->type->event->starts_at, 'D MMM YYYY') }}</span></div>@endforeach @endif
@endsection

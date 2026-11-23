@extends('layouts.app', ['title' => __('Mon billet')])
@section('content')
<section class="section"><div class="wrap gutter text-center" style="max-width:520px">
  <div class="panel p-4">
    <div class="eyebrow">{{ $ticket->type->name }}</div><h1 class="h4">{{ $ticket->type->event->tr('title') }}</h1>
    <p class="mb-3">{{ date_human($ticket->type->event->starts_at, 'dddd D MMMM YYYY, HH:mm') }}<br>{{ $ticket->type->event->venue }}, {{ $ticket->type->event->city }}</p>
    <div class="d-inline-block p-3 bg-white rounded">{!! \SimpleSoftwareIO\QrCode\Facades\QrCode::size(260)->margin(1)->generate($ticket->code) !!}</div>
    <p class="mt-3 mb-1"><b>{{ $ticket->holder_name }}</b></p><p class="mono small">{{ strtoupper(substr($ticket->code, 0, 8)) }}</p>
    @if ($ticket->checked_in_at)<span class="pill bad">{{ __('Déjà utilisé le :d', ['d' => date_human($ticket->checked_in_at, 'D MMM, HH:mm')]) }}</span>@else<span class="pill ok">{{ __('Valide') }}</span>@endif
  </div>
  <a href="{{ route('account.tickets') }}" class="btn btn-ghost mt-3">{{ __('Tous mes billets') }}</a>
</div></section>
@endsection

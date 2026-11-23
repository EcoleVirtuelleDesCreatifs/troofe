@extends('account.layout', ['title' => __('Mes commandes')])
@section('account')
@forelse ($orders as $o)
  <a href="{{ route('account.orders.show', $o) }}" class="panel d-flex gap-3 align-items-center mb-3">
    <img src="{{ $o->restaurant?->coverUrl() ?? $o->event?->coverUrl() }}" alt="" style="width:64px;height:64px;border-radius:10px;object-fit:cover">
    <div class="flex-grow-1 min-w-0"><b>{{ $o->restaurant?->name ?? $o->event?->tr('title') }}</b><div class="small muted">{{ $o->reference }} · {{ date_human($o->created_at, 'D MMM YYYY, HH:mm') }} · {{ $o->items->sum('quantity') }} {{ __('article(s)') }}</div></div>
    <div class="text-end"><div class="tnum fw-bold">{{ money($o->total) }}</div>{!! status_pill($o->status, $o->statusLabel()) !!}</div>
  </a>
@empty
  <x-empty icon="bi-bag" :text="__('Aucune commande pour le moment.')"><a href="{{ route('restaurants.index') }}" class="btn btn-brand">{{ __('Trouver un restaurant') }}</a></x-empty>
@endforelse
{{ $orders->links() }}
@endsection

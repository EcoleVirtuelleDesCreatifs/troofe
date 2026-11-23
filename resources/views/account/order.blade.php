@extends('account.layout', ['title' => __('Commande :ref', ['ref' => $order->reference])])
@section('account')
@php $steps = ['payee', 'confirmee', 'en_preparation', 'prete', 'terminee']; $pos = array_search($order->status, $steps); @endphp
@if ($order->type === 'repas' && $order->status !== 'annulee' && $order->status !== 'en_attente')
  <div class="panel mb-3"><div class="d-flex justify-content-between flex-wrap gap-2">@foreach ($steps as $i => $s)<div class="text-center flex-fill"><i class="bi {{ $pos !== false && $i <= $pos ? 'bi-check-circle-fill' : 'bi-circle' }}" style="color:{{ $pos !== false && $i <= $pos ? 'var(--ok)' : 'var(--line)' }};font-size:1.4rem"></i><div class="small">{{ __(\App\Models\Order::STATUSES[$s]) }}</div></div>@endforeach</div></div>
@endif
<div class="panel mb-3">
  <div class="d-flex justify-content-between mb-2"><b>{{ $order->restaurant?->name ?? $order->event?->tr('title') }}</b>{!! status_pill($order->status, $order->statusLabel()) !!}</div>
  @foreach ($order->items as $i)<div class="d-flex justify-content-between py-1"><span>{{ $i->quantity }} × {{ $i->name }}</span><span class="tnum">{{ money($i->total) }}</span></div>@endforeach
  @if ($order->fees)<div class="d-flex justify-content-between py-1 muted"><span>{{ __('Livraison') }}</span><span class="tnum">{{ money($order->fees) }}</span></div>@endif
  <div class="d-flex justify-content-between fw-bold border-top pt-2 mt-2"><span>{{ __('Total') }}</span><span class="tnum">{{ money($order->total) }}</span></div>
  @if ($order->delivery_address)<p class="small muted mt-2 mb-0"><i class="bi bi-geo-alt"></i> {{ $order->delivery_address }}</p>@endif
</div>
@if ($order->tickets->isNotEmpty())<a href="{{ route('account.tickets') }}" class="btn btn-brand"><i class="bi bi-ticket-perforated"></i> {{ __('Afficher mes billets') }}</a>@endif
@foreach ($order->payments->where('status', 'paid') as $p)<a href="{{ route('account.invoice', $p) }}" class="btn btn-ghost ms-2"><i class="bi bi-receipt"></i> {{ __('Facture :n', ['n' => $p->invoice_number]) }}</a>@endforeach
@endsection

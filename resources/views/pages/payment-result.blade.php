@extends('layouts.app', ['title' => __('Paiement')])
@section('content')
<x-auth-card>
  <div class="text-center">
  @if ($payment->isPaid())
    <i class="bi bi-check-circle-fill" style="font-size:3rem;color:var(--ok)"></i><h1 class="h4 mt-2">{{ __('Paiement confirmé') }}</h1>
    <p>{{ $payment->description }} — <b class="tnum">{{ money($payment->amount) }}</b></p><p class="small muted">{{ __('Un e-mail de confirmation et votre facture viennent de vous être envoyés.') }}</p>
    <a href="{{ $next }}" class="btn btn-brand">{{ __('Continuer') }}</a>
  @elseif ($payment->status === 'failed')
    <i class="bi bi-x-circle-fill" style="font-size:3rem;color:var(--bad)"></i><h1 class="h4 mt-2">{{ __('Le paiement n\'a pas abouti') }}</h1>
    <p class="muted">{{ __('Aucun montant n\'a été débité.') }}</p><a href="{{ route('payments.retry', $payment) }}" class="btn btn-brand">{{ __('Réessayer') }}</a>
  @else
    <div class="spinner-border" style="color:var(--ocre)" role="status"></div><h1 class="h4 mt-3">{{ __('Paiement en cours de confirmation') }}</h1>
    <p class="muted">{{ __('Validez l\'opération sur votre téléphone si demandé. Cette page se met à jour automatiquement ; vous recevrez aussi un e-mail.') }}</p>
    <meta http-equiv="refresh" content="6">
  @endif
  </div>
</x-auth-card>
@endsection

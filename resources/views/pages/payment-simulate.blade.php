@extends('layouts.app', ['title' => __('Paiement')])
@section('content')
<x-auth-card>
  <div class="notice info mb-3"><i class="bi bi-cone-striped"></i><div>{{ __('Mode test : aucun montant n\'est débité. En production, cette étape est remplacée par la page sécurisée CinetPay.') }}</div></div>
  <h1 class="h4">{{ __('Paiement') }}</h1>
  <p class="mb-1">{{ $payment->description }}</p><p class="fs-3 fw-bold tnum">{{ money($payment->amount) }}</p>
  <form method="post" action="{{ route('payments.decide', $payment) }}" class="d-grid gap-2">@csrf
    <select name="method" class="form-select" aria-label="{{ __('Moyen de paiement') }}"><option value="OM">Orange Money</option><option value="MOMO">MTN MoMo</option><option value="MOOV">Moov Money</option><option value="WAVE">Wave</option><option value="CARD">{{ __('Carte bancaire') }}</option></select>
    <button name="decision" value="paid" class="btn btn-brand"><i class="bi bi-check-lg"></i> {{ __('Simuler un paiement réussi') }}</button>
    <button name="decision" value="failed" class="btn btn-ghost">{{ __('Simuler un échec') }}</button>
  </form>
</x-auth-card>
@endsection

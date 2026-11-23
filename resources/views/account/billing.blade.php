@extends('account.layout', ['title' => __('Abonnement et factures')])
@section('account')
<div class="panel mb-4">
  <div class="d-flex justify-content-between flex-wrap gap-3 align-items-start">
    <div><div class="eyebrow">{{ __('Formule actuelle') }}</div><h2 class="h3 mb-1">{{ $plan->tr('name') }}</h2>
      @if ($subscription)
        <p class="mb-1">{!! status_pill($subscription->status, $subscription->statusLabel()) !!} · {{ __('Cycle :c', ['c' => __($subscription->cycleLabel())]) }} · {{ money($subscription->amount()) }}</p>
        <p class="small muted mb-0">@if ($subscription->status === 'cancelled'){{ __('Prend fin le :d, sans renouvellement.', ['d' => date_human($subscription->ends_at)]) }}@else{{ __('Échéance le :d (dans :n jours). Rappels par e-mail 7, 3 et 1 jour avant.', ['d' => date_human($subscription->ends_at), 'n' => $subscription->daysLeft()]) }}@endif</p>
      @else
        <p class="small muted mb-0">{{ __('Formule gratuite. Passez à une formule supérieure pour publier plus de fiches et être mis en avant.') }}</p>
      @endif
    </div>
    <div class="text-end"><div class="small muted">{{ __('Fiches actives') }}</div><div class="fs-4 fw-bold tnum">{{ $used }} / {{ $plan->max_listings }}</div>
      <div class="progress mt-1" style="height:6px;width:160px"><div class="progress-bar" style="width:{{ min(100, $plan->max_listings ? $used / $plan->max_listings * 100 : 0) }}%;background:var(--brun)"></div></div></div>
  </div>
  <div class="d-flex gap-2 flex-wrap mt-3">
    @if ($subscription && in_array($subscription->status, ['active', 'past_due'], true))
      <a href="{{ route('subscription.renew', $subscription) }}" class="btn btn-brand btn-sm"><i class="bi bi-arrow-repeat"></i> {{ __('Renouveler maintenant') }}</a>
      <form method="post" action="{{ route('subscription.cancel', $subscription) }}" onsubmit="return confirm(@js(__('Ne pas renouveler votre abonnement ? Il reste actif jusqu\'à l\'échéance.')))">@csrf<button class="btn btn-ghost btn-sm">{{ __('Résilier') }}</button></form>
    @elseif ($subscription && $subscription->status === 'cancelled')
      <form method="post" action="{{ route('subscription.resume', $subscription) }}">@csrf<button class="btn btn-brand btn-sm">{{ __('Réactiver le renouvellement') }}</button></form>
    @endif
    <a href="{{ route('pricing') }}" class="btn btn-ghost btn-sm">{{ __('Changer de formule') }}</a>
  </div>
</div>

<h2 class="h5">{{ __('Paiements et factures') }}</h2>
<div class="panel p-0 table-responsive">
  <table class="table mb-0 align-middle"><thead><tr><th>{{ __('Date') }}</th><th>{{ __('Objet') }}</th><th>{{ __('Montant') }}</th><th>{{ __('Statut') }}</th><th></th></tr></thead><tbody>
    @forelse ($payments as $p)
      <tr><td class="small">{{ date_human($p->created_at, 'D MMM YYYY') }}</td><td class="small">{{ $p->description }}</td><td class="tnum">{{ money($p->amount) }}</td><td>{!! status_pill($p->status, $p->statusLabel()) !!}</td>
        <td class="text-end">@if ($p->isPaid())<a href="{{ route('account.invoice', $p) }}" class="btn btn-ghost btn-sm"><i class="bi bi-download"></i> {{ $p->invoice_number }}</a>@elseif ($p->status === 'failed')<a href="{{ route('payments.retry', $p) }}" class="btn btn-soft btn-sm">{{ __('Réessayer') }}</a>@endif</td></tr>
    @empty
      <tr><td colspan="5" class="text-center muted py-4">{{ __('Aucun paiement.') }}</td></tr>
    @endforelse
  </tbody></table>
</div>
<div class="mt-3">{{ $payments->links() }}</div>
@if ($history->count() > 1)<h2 class="h6 mt-4 muted">{{ __('Historique des abonnements') }}</h2>@foreach ($history as $s)<div class="d-flex justify-content-between small py-2 border-bottom"><span>{{ $s->plan->tr('name') }} · {{ __($s->cycleLabel()) }}</span><span>{!! status_pill($s->status, $s->statusLabel()) !!} {{ $s->ends_at ? date_human($s->ends_at, 'D MMM YYYY') : '' }}</span></div>@endforeach @endif
@endsection

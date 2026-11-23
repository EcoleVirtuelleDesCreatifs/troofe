@extends('layouts.app', ['title' => __('Formules pro')])
@section('content')
<section class="section"><div class="wrap gutter">
  <div class="text-center mx-auto mb-5" style="max-width:720px"><div class="eyebrow">{{ __('Professionnels') }}</div><h1 class="h2">{{ __('Une formule pour chaque activité') }}</h1>
    <p class="muted">{{ __('Restaurants, troupes, artistes, artisans et organisateurs. Paiement par Mobile Money ou carte, sans engagement : rappels par e-mail avant chaque échéance, résiliation en un clic.') }}</p>
    <div class="segmented d-inline-flex" role="group"><button type="button" class="on" data-cycle="monthly">{{ __('Mensuel') }}</button><button type="button" data-cycle="yearly">{{ __('Annuel') }} <span class="pill ok ms-1">{{ __('2 mois offerts') }}</span></button></div></div>
  <div class="row g-4 justify-content-center">
    @foreach ($plans as $i => $plan)
      <div class="col-md-6 col-lg-3"><div class="plan-card {{ $plan->slug === 'pro' ? 'feat' : '' }}">
        @if ($plan->slug === 'pro')<span class="tag spon align-self-start">{{ __('Le plus choisi') }}</span>@endif
        <h2 class="h4 mb-0">{{ $plan->tr('name') }}</h2><p class="small muted mb-2">{{ $plan->tr('tagline') }}</p>
        <div><span class="price" data-m="{{ money($plan->price_monthly) }}" data-y="{{ money($plan->price_yearly) }}">{{ $plan->isFree() ? __('Gratuit') : money($plan->price_monthly) }}</span>@unless ($plan->isFree())<span class="small muted" data-per>/ {{ __('mois') }}</span>@endunless</div>
        <ul>@foreach ($plan->tr('features') ?? [] as $f)<li><i class="bi bi-check2"></i>{{ $f }}</li>@endforeach</ul>
        <div class="mt-auto">
          @if ($current && $current->plan_id === $plan->id)
            <a href="{{ route('account.billing') }}" class="btn btn-soft w-100">{{ __('Votre formule actuelle') }}</a>
          @elseif ($plan->isFree())
            <a href="{{ auth()->check() ? route('partner') : route('register') }}" class="btn btn-ghost w-100">{{ __('Commencer gratuitement') }}</a>
          @else
            @auth<form method="post" action="{{ route('subscription.subscribe', $plan) }}">@csrf<input type="hidden" name="cycle" value="monthly" data-cycle-input><button class="btn {{ $plan->slug === 'pro' ? 'btn-brand' : 'btn-ghost' }} w-100">{{ __('Choisir :plan', ['plan' => $plan->tr('name')]) }}</button></form>
            @else<a href="{{ route('register') }}" class="btn btn-ghost w-100">{{ __('Créer un compte') }}</a>@endauth
          @endif
        </div>
      </div></div>
    @endforeach
  </div>
  <div class="row g-4 mt-4">
    <div class="col-md-4"><div class="panel h-100"><i class="bi bi-bell fs-4" style="color:var(--ocre)"></i><h3 class="h6 mt-2">{{ __('Rappels automatiques') }}</h3><p class="small muted mb-0">{{ __('E-mail 7, 3 et 1 jour avant l\'échéance, puis délai de grâce de 3 jours. Rien n\'est supprimé à l\'expiration.') }}</p></div></div>
    <div class="col-md-4"><div class="panel h-100"><i class="bi bi-phone fs-4" style="color:var(--ocre)"></i><h3 class="h6 mt-2">{{ __('Paiement local') }}</h3><p class="small muted mb-0">{{ __('Orange Money, MTN MoMo, Moov Money, Wave et cartes Visa/Mastercard. Facture PDF envoyée par e-mail.') }}</p></div></div>
    <div class="col-md-4"><div class="panel h-100"><i class="bi bi-x-circle fs-4" style="color:var(--ocre)"></i><h3 class="h6 mt-2">{{ __('Sans engagement') }}</h3><p class="small muted mb-0">{{ __('Résiliez depuis votre espace : la formule reste active jusqu\'à la fin de la période payée.') }}</p></div></div>
  </div>
</div></section>
@push('scripts')
<script>
document.querySelectorAll('[data-cycle]').forEach(b => b.addEventListener('click', () => {
  const y = b.dataset.cycle === 'yearly';
  document.querySelectorAll('[data-cycle]').forEach(x => x.classList.toggle('on', x === b));
  document.querySelectorAll('[data-m]').forEach(p => { if (p.dataset.m.startsWith('0')) return; p.textContent = y ? p.dataset.y : p.dataset.m; });
  document.querySelectorAll('[data-per]').forEach(p => p.textContent = y ? @js('/ '.__('an')) : @js('/ '.__('mois')));
  document.querySelectorAll('[data-cycle-input]').forEach(i => i.value = y ? 'yearly' : 'monthly');
}));
</script>
@endpush
@endsection

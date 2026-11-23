@extends('account.layout', ['title' => __('Mes abonnements'), 'subtitle' => __('Fiches suivies et lettre d\'information')])
@section('account')
<div class="panel mb-4">
  <h2 class="h5">{{ __('Lettre d\'information') }}</h2>
  @if ($newsletter && $newsletter->isActive())
    <p class="mb-2">{{ __('Vous êtes inscrit (:freq, en :lang).', ['freq' => $newsletter->frequency === 'monthly' ? __('mensuelle') : __('hebdomadaire'), 'lang' => $newsletter->locale === 'en' ? 'English' : 'français']) }}</p>
    <a class="btn btn-ghost btn-sm" href="{{ route('newsletter.manage', $newsletter->token) }}">{{ __('Gérer mes thèmes') }}</a>
    <a class="btn btn-link btn-sm" href="{{ route('newsletter.unsubscribe', $newsletter->token) }}">{{ __('Me désinscrire') }}</a>
  @elseif ($newsletter && ! $newsletter->confirmed_at)
    <p class="mb-0 small">{{ __('Inscription en attente : cliquez sur le lien reçu par e-mail pour la confirmer.') }}</p>
  @else
    <form method="post" action="{{ route('newsletter.subscribe') }}" class="d-flex gap-2 flex-wrap">@csrf<input type="hidden" name="email" value="{{ auth()->user()->email }}"><span class="small muted align-self-center">{{ auth()->user()->email }}</span><button class="btn btn-brand btn-sm">{{ __('M\'inscrire') }}</button></form>
  @endif
</div>
<h2 class="h5">{{ __('Fiches suivies') }}</h2>
<p class="small muted">{{ __('Vous êtes prévenu de leurs nouveaux événements et nouvelles offres (réglable dans Notifications).') }}</p>
@forelse ($follows as $f)
  @php $m = $f->followable; $url = $m instanceof \App\Models\Restaurant ? route('restaurants.show', $m) : ($m instanceof \App\Models\Talent ? $m->url() : '#'); $type = $m instanceof \App\Models\Restaurant ? 'restaurant' : 'talent'; @endphp
  <div class="panel d-flex gap-3 align-items-center mb-2"><img src="{{ $m->coverUrl() }}" alt="" style="width:56px;height:56px;border-radius:10px;object-fit:cover"><div class="flex-grow-1"><a href="{{ $url }}" class="fw-bold">{{ $m->name }}</a><div class="small muted">{{ __('Suivi depuis le :d', ['d' => date_human($f->created_at, 'D MMM YYYY')]) }}</div></div><x-follow-button :type="$type" :model="$m" /></div>
@empty
  <x-empty icon="bi-broadcast" :text="__('Vous ne suivez encore aucune fiche. Cliquez sur « Suivre » sur un restaurant ou un artiste.')" />
@endforelse
@endsection

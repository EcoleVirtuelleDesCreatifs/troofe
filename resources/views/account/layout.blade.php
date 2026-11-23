@extends('layouts.app', ['title' => $title ?? __('Mon compte')])
@section('content')
@php
  $u = auth()->user();
  $nav = [
    ['account.dashboard', 'bi-grid', __('Tableau de bord'), 0],
    ['account.profile', 'bi-person', __('Mon profil'), 0],
    ['account.notifications', 'bi-bell', __('Notifications'), $u->unreadNotifications()->count()],
    ['account.orders', 'bi-bag', __('Mes commandes'), 0],
    ['account.tickets', 'bi-ticket-perforated', __('Mes billets'), 0],
    ['account.bookings', 'bi-calendar2-check', __('Mes réservations'), $u->bookings()->where('status', 'devis_envoye')->count()],
    ['account.favorites', 'bi-heart', __('Mes favoris'), 0],
    ['account.follows', 'bi-broadcast', __('Mes abonnements'), 0],
    ['account.billing', 'bi-receipt', __('Abonnement et factures'), 0],
  ];
@endphp
<section class="section"><div class="wrap gutter"><div class="space">
  <aside class="panel p-2 space-nav">
    <div class="d-flex gap-2 align-items-center p-2 mb-1 border-bottom"><x-avatar :user="$u" :size="40" /><div class="min-w-0"><b class="d-block text-truncate">{{ $u->name }}</b><span class="small muted">{{ $u->isPro() ? __('Espace membre et pro') : __('Espace membre') }}</span></div></div>
    @foreach ($nav as [$route, $icon, $label, $n])<a href="{{ route($route) }}" class="{{ request()->routeIs($route) ? 'on' : '' }}"><i class="bi {{ $icon }}"></i> {{ $label }}@if ($n)<span class="n">{{ $n }}</span>@endif</a>@endforeach
    @if ($u->isPro())<a href="{{ url('/pro') }}"><i class="bi bi-briefcase"></i> {{ __('Espace pro') }} <i class="bi bi-box-arrow-up-right ms-auto small"></i></a>@else<a href="{{ route('partner') }}"><i class="bi bi-briefcase"></i> {{ __('Devenir partenaire') }}</a>@endif
    <form method="post" action="{{ route('logout') }}">@csrf<button class="btn btn-link w-100 text-start px-3 py-2 text-decoration-none" style="color:var(--ink)"><i class="bi bi-box-arrow-right me-2"></i>{{ __('Se déconnecter') }}</button></form>
  </aside>
  <div class="min-w-0">
    <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-3"><div><h1 class="h3 mb-1">{{ $title ?? '' }}</h1>@isset($subtitle)<p class="muted mb-0">{{ $subtitle }}</p>@endisset</div>@yield('actions')</div>
    @if (! $u->hasVerifiedEmail())<div class="notice warn mb-3"><i class="bi bi-envelope-exclamation"></i><div>{{ __('Confirmez votre adresse e-mail pour commander et réserver.') }} <a href="{{ route('verification.notice') }}">{{ __('Renvoyer le lien') }}</a></div></div>@endif
    @yield('account')
  </div>
</div></div></section>
@endsection

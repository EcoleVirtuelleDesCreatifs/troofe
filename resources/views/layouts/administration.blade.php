<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ __('Administration') }} — Trôofê</title>
  <link rel="icon" href="{{ asset('assets/img/logo/favicon.svg') }}" type="image/svg+xml">
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:wght@500;600;700;800&family=Figtree:wght@400;500;600;700&display=swap">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
  <link rel="stylesheet" href="{{ asset('assets/css/troofe.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/culture.css') }}">
  <link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
  @livewireStyles
</head>
<body class="is-admin">
<a class="visually-hidden-focusable" href="#admin-content">{{ __('Aller au contenu') }}</a>
<div class="admin-shell">
  <aside class="admin-side" id="admin-side" aria-label="{{ __('Navigation administration') }}">
    <a href="{{ route('home') }}" class="logo"><img src="{{ asset('assets/img/logo/symbole.svg') }}" alt="" style="display:block"><div><b>Trôofê</b><div class="small muted">{{ __('Administration') }}</div></div></a>
    @foreach (filament()->getNavigation() as $group)
      @if ($group->getLabel())<div class="grp">{{ $group->getLabel() }}</div>@endif
      @foreach ($group->getItems() as $item)
        <a class="side-link {{ $item->isActive() ? 'active' : '' }}" href="{{ $item->getUrl() }}" @if ($item->isActive()) aria-current="page" @endif>{{ $item->getLabel() }}@if ($item->getBadge())<span class="count">{{ $item->getBadge() }}</span>@endif</a>
      @endforeach
    @endforeach
    <div class="grp">{{ __('Raccourcis') }}</div>
    <a class="side-link" href="{{ route('home') }}"><i class="bi bi-box-arrow-up-right"></i>{{ __('Voir le site public') }}</a>
    <a class="side-link" href="{{ route('account.dashboard') }}"><i class="bi bi-person"></i>{{ __('Espace membre') }}</a>
  </aside>
  <div class="admin-main">
    <header class="admin-top gutter">
      <button type="button" class="btn btn-ghost btn-icon admin-burger" aria-controls="admin-side" aria-expanded="false" aria-label="{{ __('Menu') }}" onclick="const side = document.getElementById('admin-side'); this.setAttribute('aria-expanded', side.classList.toggle('open'))"><i class="bi bi-list"></i></button>
      <div class="small muted">{{ __('Administration') }} <i class="bi bi-chevron-right"></i> {{ __('Tableau de bord') }}</div>
      <div class="ms-auto d-flex align-items-center gap-3"><span class="small d-none d-md-inline">{{ auth()->user()->name }}</span><form method="post" action="{{ route('logout') }}">@csrf<button class="btn btn-ghost btn-sm">{{ __('Se déconnecter') }}</button></form></div>
    </header>
    <main class="admin-content gutter" id="admin-content">{{ $slot }}</main>
  </div>
</div>
@livewireScripts
</body>
</html>

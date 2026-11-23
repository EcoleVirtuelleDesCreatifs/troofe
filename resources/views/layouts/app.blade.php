<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<meta name="csrf-token" content="{{ csrf_token() }}">
<title>{{ isset($title) ? $title.' — ' : '' }}Trôofê</title>
<meta name="description" content="{{ $description ?? __('Trôofê, la maison numérique de la gastronomie africaine : restaurants, encyclopédie culinaire, groupes de danse, artistes, artisans et agenda.') }}">
<link rel="canonical" href="{{ url()->current() }}">
@foreach (\App\Http\Middleware\SetLocale::LOCALES as $code => $label)
<link rel="alternate" hreflang="{{ $code }}" href="{{ url()->current() }}?lang={{ $code }}">
@endforeach
<meta property="og:title" content="{{ $title ?? 'Trôofê' }}">
<meta property="og:description" content="{{ $description ?? __('La table africaine, en un seul lieu.') }}">
@isset($ogImage)<meta property="og:image" content="{{ $ogImage }}">@endisset
<meta property="og:locale" content="{{ app()->getLocale() === 'en' ? 'en_US' : 'fr_FR' }}">
<link rel="icon" href="{{ asset('assets/img/logo/favicon.svg') }}" type="image/svg+xml">
<link rel="preconnect" href="https://fonts.googleapis.com"><link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500;12..96,650;12..96,700;12..96,800&family=Figtree:wght@400;500;600;700&family=JetBrains+Mono:wght@500;600&display=swap">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
<link rel="stylesheet" href="{{ asset('assets/css/troofe.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/culture.css') }}">
<link rel="stylesheet" href="{{ asset('assets/css/app.css') }}">
@stack('head')
</head>
<body>
<a class="visually-hidden-focusable" href="#main">{{ __('Aller au contenu') }}</a>

<header class="topbar" id="topbar">
  <div class="wrap gutter inner">
    <a href="{{ route('home') }}" class="brand" aria-label="{{ __('Trôofê — accueil') }}"><img src="{{ asset('assets/img/logo/logo-horizontal.svg') }}" alt="Trôofê" class="brand-logo"></a>
    @include('partials.nav', ['cls' => 'desk'])
    <div class="ms-auto d-flex align-items-center gap-1">
      <button class="btn btn-ghost border-0 kbd-search d-none d-lg-inline-flex" data-search aria-label="{{ __('Rechercher') }}"><i class="bi bi-search"></i><span>{{ __('Rechercher') }}</span><kbd>Ctrl K</kbd></button>
      <button class="btn btn-ghost btn-icon border-0 d-lg-none" data-search aria-label="{{ __('Rechercher') }}"><i class="bi bi-search"></i></button>
      <div class="dropdown">
        <button class="btn btn-ghost btn-sm border-0 fw-semibold" data-bs-toggle="dropdown" aria-label="{{ __('Langue') }}">{{ strtoupper(app()->getLocale()) }}</button>
        <ul class="dropdown-menu dropdown-menu-end">
          @foreach (\App\Http\Middleware\SetLocale::LOCALES as $code => $label)
            <li><a class="dropdown-item {{ app()->getLocale() === $code ? 'active' : '' }}" href="{{ locale_url($code) }}" hreflang="{{ $code }}" lang="{{ $code }}">{{ $label }}</a></li>
          @endforeach
        </ul>
      </div>
      @auth
        <div class="dropdown">
          <button class="btn btn-ghost btn-icon border-0 position-relative" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-label="{{ __('Notifications') }}">
            <i class="bi bi-bell"></i>@if ($unreadCount)<span class="cart-badge" style="background:var(--bad)">{{ $unreadCount > 9 ? '9+' : $unreadCount }}</span>@endif
          </button>
          <div class="dropdown-menu dropdown-menu-end notif-menu p-0">
            <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom"><b>{{ __('Notifications') }}</b>
              @if ($unreadCount)<form method="post" action="{{ route('account.notifications.readAll') }}">@csrf<button class="btn btn-link btn-sm p-0">{{ __('Tout lire') }}</button></form>@endif
            </div>
            @forelse ($unreadNotifications as $n)
              <a class="notif-item unread" href="{{ route('account.notifications.open', $n->id) }}"><i class="bi {{ $n->data['icon'] ?? 'bi-bell-fill' }}"></i><div><div class="small fw-semibold">{{ $n->data['title'] ?? '' }}</div><div class="small muted">{{ $n->created_at->diffForHumans() }}</div></div></a>
            @empty
              <div class="p-3 small muted">{{ __('Aucune nouvelle notification.') }}</div>
            @endforelse
            <a class="d-block text-center small py-2 border-top" href="{{ route('account.notifications') }}">{{ __('Tout voir') }}</a>
          </div>
        </div>
        <div class="dropdown">
          <button class="btn btn-ghost btn-icon border-0" data-bs-toggle="dropdown" aria-label="{{ __('Mon compte') }}"><x-avatar :user="auth()->user()" :size="32" /></button>
          <ul class="dropdown-menu dropdown-menu-end">
            <li class="px-3 py-2 small"><b>{{ auth()->user()->name }}</b><br><span class="muted">{{ auth()->user()->email }}</span></li>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="{{ route('account.dashboard') }}"><i class="bi bi-grid me-2"></i>{{ __('Tableau de bord') }}</a></li>
            <li><a class="dropdown-item" href="{{ route('account.orders') }}"><i class="bi bi-bag me-2"></i>{{ __('Mes commandes') }}</a></li>
            <li><a class="dropdown-item" href="{{ route('account.tickets') }}"><i class="bi bi-ticket-perforated me-2"></i>{{ __('Mes billets') }}</a></li>
            <li><a class="dropdown-item" href="{{ route('account.bookings') }}"><i class="bi bi-calendar2-check me-2"></i>{{ __('Mes réservations') }}</a></li>
            <li><a class="dropdown-item" href="{{ route('account.follows') }}"><i class="bi bi-broadcast me-2"></i>{{ __('Mes abonnements') }}</a></li>
            @if (auth()->user()->isPro())<li><a class="dropdown-item" href="{{ url('/pro') }}"><i class="bi bi-briefcase me-2"></i>{{ __('Espace pro') }}</a></li>@endif
            @if (auth()->user()->isAdmin())<li><a class="dropdown-item" href="{{ url('/admin') }}"><i class="bi bi-shield-lock me-2"></i>{{ __('Administration') }}</a></li>@endif
            <li><hr class="dropdown-divider"></li>
            <li><form method="post" action="{{ route('logout') }}">@csrf<button class="dropdown-item"><i class="bi bi-box-arrow-right me-2"></i>{{ __('Se déconnecter') }}</button></form></li>
          </ul>
        </div>
      @else
        <a href="{{ route('login') }}" class="btn btn-brand btn-sm ms-1">{{ __('Mon compte') }}</a>
      @endauth
      <button class="btn btn-ghost btn-icon border-0 burger" data-bs-toggle="offcanvas" data-bs-target="#mnav" aria-label="{{ __('Menu') }}"><i class="bi bi-list fs-5"></i></button>
    </div>
  </div>
</header>

<div class="offcanvas offcanvas-end" tabindex="-1" id="mnav" aria-label="{{ __('Menu') }}">
  <div class="offcanvas-header"><img src="{{ asset('assets/img/logo/logo-horizontal.svg') }}" alt="Trôofê" class="brand-logo"><button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="{{ __('Fermer') }}"></button></div>
  <div class="offcanvas-body">@include('partials.nav', ['cls' => ''])
    <div class="d-flex gap-2 mt-4">@foreach (\App\Http\Middleware\SetLocale::LOCALES as $code => $label)<a class="btn btn-sm {{ app()->getLocale() === $code ? 'btn-brand' : 'btn-ghost' }}" href="{{ locale_url($code) }}">{{ $label }}</a>@endforeach</div>
  </div>
</div>

<main id="main">
  @include('partials.flash')
  @yield('content')
</main>

<footer class="site">
  <div class="wrap gutter cols">
    <div>
      <img src="{{ asset('assets/img/logo/logo-horizontal-negatif.svg') }}" alt="Trôofê" class="foot-logo">
      <p class="small muted mt-3" style="max-width:36ch">{{ __('La maison numérique de la gastronomie, de la culture et des savoir-faire africains.') }}</p>
      <form method="post" action="{{ route('newsletter.subscribe') }}" class="foot-news mt-3">@csrf
        <label for="nl-email" class="small fw-semibold d-block mb-2">{{ __('La lettre Trôofê, chaque jeudi') }}</label>
        <div class="d-flex gap-2"><input type="email" required name="email" id="nl-email" class="form-control form-control-sm" placeholder="{{ __('votre@email.com') }}"><input type="text" name="website" class="d-none" tabindex="-1" autocomplete="off"><button class="btn btn-ocre btn-sm">{{ __('S\'inscrire') }}</button></div>
      </form>
    </div>
    <div><h6>{{ __('Découvrir') }}</h6><a href="{{ route('restaurants.index') }}">{{ __('Restaurants') }}</a><a href="{{ route('dishes.index') }}">{{ __('Encyclopédie') }}</a><a href="{{ route('articles.index') }}">{{ __('Actualités') }}</a><a href="{{ route('events.index') }}">{{ __('Agenda') }}</a></div>
    <div><h6>{{ __('Culture') }}</h6><a href="{{ route('culture.index', 'danse') }}">{{ __('Groupes de danse') }}</a><a href="{{ route('culture.index', 'prestations') }}">{{ __('Prestations artistiques') }}</a><a href="{{ route('culture.index', 'artisans') }}">{{ __('Artisans') }}</a><a href="{{ route('culture.hub') }}#pack">{{ __('Composer ma soirée') }}</a></div>
    <div><h6>{{ __('Professionnels') }}</h6><a href="{{ route('partner') }}">{{ __('Devenir partenaire') }}</a><a href="{{ route('pricing') }}">{{ __('Formules pro') }}</a><a href="{{ route('advertise') }}">{{ __('Annoncer sur Trôofê') }}</a><a href="{{ url('/pro') }}">{{ __('Espace pro') }}</a></div>
    <div><h6>{{ __('Trôofê') }}</h6><a href="{{ route('page', 'a-propos') }}">{{ __('À propos') }}</a><a href="{{ route('contact') }}">{{ __('Contact') }}</a><a href="{{ route('page', 'conditions') }}">{{ __('Conditions') }}</a><a href="{{ route('page', 'confidentialite') }}">{{ __('Confidentialité') }}</a><a href="{{ route('page', 'mentions-legales') }}">{{ __('Mentions légales') }}</a></div>
  </div>
  <div class="wrap gutter d-flex justify-content-between flex-wrap gap-2 small muted pb-4 pt-3"><span>© {{ date('Y') }} Trôofê</span><span>@foreach (\App\Http\Middleware\SetLocale::LOCALES as $code => $label)<a href="{{ locale_url($code) }}" class="d-inline ms-2 {{ app()->getLocale() === $code ? 'fw-bold' : '' }}">{{ $label }}</a>@endforeach</span></div>
</footer>

<div class="modal fade" id="searchModal" tabindex="-1" aria-label="{{ __('Rechercher') }}">
  <div class="modal-dialog modal-lg modal-dialog-scrollable"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title"><i class="bi bi-search me-2"></i>{{ __('Rechercher sur Trôofê') }}</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Fermer') }}"></button></div>
    <div class="modal-body"><form action="{{ route('search') }}"><input class="form-control form-control-lg" name="q" id="search-q" placeholder="{{ __('Restaurant, plat, artiste, artisan, événement…') }}" autocomplete="off"></form><div id="search-res" class="pal-res mt-3"></div></div>
  </div></div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>window.TROOFE = { searchUrl: @json(route('search')), locale: @json(app()->getLocale()), i18n: { noResult: @json(__('Aucun résultat.')), error: @json(__('Une erreur est survenue. Réessayez.')) } };</script>
<script src="{{ asset('assets/js/troofe.js') }}" defer></script>
@stack('scripts')
</body>
</html>

@php
  $r = request()->route()?->getName() ?? '';
  $items = [
    ['home', __('Accueil'), $r === 'home'],
    ['restaurants.index', __('Restaurants'), str_starts_with($r, 'restaurants.')],
    ['dishes.index', __('Plats & Encyclopédie'), str_starts_with($r, 'dishes.')],
    ['culture.hub', __('Culture & savoir-faire'), str_starts_with($r, 'culture.')],
    ['articles.index', __('Actualités'), str_starts_with($r, 'articles.')],
    ['events.index', __('Événements'), str_starts_with($r, 'events.')],
    ['partner', __('Devenir partenaire'), $r === 'partner' || $r === 'pricing'],
  ];
@endphp
<nav class="navlinks {{ $cls }}">
  @foreach ($items as [$name, $label, $on])
    <a href="{{ route($name) }}" class="{{ $on ? 'active' : '' }}">{{ $label }}</a>
  @endforeach
</nav>

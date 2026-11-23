@php
  $r = request()->route()?->getName() ?? '';
  $cultureOn = str_starts_with($r, 'culture.');
  $items = [
    ['home', __('Accueil'), $r === 'home'],
    ['restaurants.index', __('Restaurants'), str_starts_with($r, 'restaurants.')],
    ['dishes.index', __('Encyclopédie'), str_starts_with($r, 'dishes.')],
    ['culture', __('Culture'), $cultureOn],
    ['events.index', __('Événements'), str_starts_with($r, 'events.')],
    ['articles.index', __('Actualités'), str_starts_with($r, 'articles.')],
    ['advertise', __('Publicité'), $r === 'advertise'],
    ['partner', __('Devenir partenaire'), $r === 'partner' || $r === 'pricing'],
  ];
  $cult = [
    [route('culture.hub'), __('Vue d\'ensemble'), 'bi-grid', __('Composer une soirée complète')],
    [route('culture.index', 'danse'), __('Groupes de danse'), 'bi-people', __('Troupes traditionnelles et urbaines')],
    [route('culture.index', 'prestations'), __('Prestations artistiques'), 'bi-music-note-beamed', __('Griots, musiciens, DJ, conteurs')],
    [route('culture.index', 'artisans'), __('Artisans'), 'bi-hammer', __('Tissage, poterie, sculpture')],
  ];
@endphp
<nav class="navlinks {{ $cls }}">
  @foreach ($items as [$name, $label, $on])
    @if ($name === 'culture')
      @if ($cls === 'desk')
        <div class="dropdown nav-dd"><a href="{{ route('culture.hub') }}" class="dd-t {{ $on ? 'active' : '' }}" data-bs-toggle="dropdown" aria-expanded="false" role="button">{{ $label }} <i class="bi bi-chevron-down dd-caret"></i></a>
          <div class="dropdown-menu nav-dd-menu">@foreach ($cult as [$h, $t, $i, $d])<a class="dropdown-item {{ url()->current() === $h ? 'on' : '' }}" href="{{ $h }}"><i class="bi {{ $i }}"></i><span><b>{{ $t }}</b><small>{{ $d }}</small></span></a>@endforeach</div>
        </div>
      @else
        <a href="{{ route('culture.hub') }}" class="{{ $on ? 'active' : '' }}">{{ $label }}</a>
        @foreach (array_slice($cult, 1) as [$h, $t])<a href="{{ $h }}" class="sub {{ url()->current() === $h ? 'active' : '' }}">{{ $t }}</a>@endforeach
      @endif
    @else
      <a href="{{ route($name) }}" class="{{ $on ? 'active' : '' }}">{{ $label }}</a>
    @endif
  @endforeach
</nav>

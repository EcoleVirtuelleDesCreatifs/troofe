<nav class="cu-switch" aria-label="{{ __('Rubriques culture') }}">
  <a href="{{ route('culture.hub') }}" class="{{ $current === 'hub' ? 'on' : '' }}"><i class="bi bi-grid"></i> {{ __('Vue d\'ensemble') }}</a>
  @foreach (\App\Models\Talent::TYPES as $t => $m)
    <a href="{{ route('culture.index', $m['route']) }}" class="{{ $current === $t ? 'on' : '' }}"><i class="bi {{ $m['icon'] }}"></i> {{ __($m['plural']) }}</a>
  @endforeach
</nav>

@foreach (['success' => 'ok', 'info' => 'info', 'warning' => 'warn'] as $key => $tone)
  @if (session($key))
    <div class="wrap gutter pt-3"><div class="notice {{ $tone }} flash" role="status"><i class="bi {{ $tone === 'ok' ? 'bi-check-circle' : 'bi-info-circle' }}"></i><div>{{ session($key) }}</div></div></div>
  @endif
@endforeach
@if ($errors->any() && ! ($hideErrors ?? false))
  <div class="wrap gutter pt-3"><div class="notice warn" role="alert"><i class="bi bi-exclamation-triangle"></i><div>@foreach ($errors->all() as $e)<div>{{ $e }}</div>@endforeach</div></div></div>
@endif

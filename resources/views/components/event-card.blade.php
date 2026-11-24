@props(['event'])
@php $e = $event; $min = $e->minPrice(); @endphp
<a class="card-x" href="{{ route('events.show', $e) }}">
  <div class="cover"><img src="{{ $e->coverUrl() }}" alt="{{ $e->tr('title') }}" loading="lazy" decoding="async" width="800" height="500">
    <div class="tag-float"><span class="tag">{{ __($e->category) }}</span>@if (($e->status ?? null) === 'cancelled')<span class="tag" style="background:var(--bad);color:#fff">{{ __('Annulé') }}</span>@endif</div>
  </div>
  <div class="body"><div class="eyebrow">{{ date_human($e->starts_at, 'ddd D MMMM') }} · {{ $e->starts_at->format('H:i') }}</div><h3>{{ $e->tr('title') }}</h3>
    <div class="meta"><span><i class="bi bi-geo-alt"></i> {{ $e->venue }}, {{ $e->city }}</span></div>
    <div class="small fw-semibold mt-1">@if ($min === null){{ __('Entrée libre') }}@elseif ($min === 0){{ __('Gratuit') }}@else{{ __('Dès') }} {{ money($min) }}@endif@if ($e->ticketTypes->isNotEmpty() && $e->remaining() === 0) · <span class="pill bad">{{ __('Complet') }}</span>@endif</div></div>
</a>

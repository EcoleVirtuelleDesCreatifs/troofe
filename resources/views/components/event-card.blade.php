@props(['event'])
@php $e = $event; $min = $e->minPrice(); @endphp
<a class="card-x" href="{{ route('events.show', $e) }}">
  <div class="cover"><img src="{{ $e->coverUrl() }}" alt="" loading="lazy">
    <div class="tag-float"><span class="tag">{{ __($e->category) }}</span></div>
    <span class="date-chip"><b>{{ $e->starts_at->format('d') }}</b><span>{{ $e->starts_at->locale(app()->getLocale())->isoFormat('MMM') }}</span></span>
  </div>
  <div class="body">
    <h3>{{ $e->tr('title') }}</h3>
    <div class="meta"><span><i class="bi bi-clock"></i> {{ date_human($e->starts_at, 'ddd D MMM, HH:mm') }}</span><span><i class="bi bi-geo-alt"></i> {{ $e->city }}</span></div>
    <div class="cu-foot"><span>@if ($min === null)<span class="small muted">{{ __('Entrée libre') }}</span>@elseif ($min === 0){{ __('Gratuit') }}@else{{ __('Dès') }} <b class="tnum">{{ money($min) }}</b>@endif</span>@if ($e->ticketTypes->isNotEmpty() && $e->remaining() === 0)<span class="pill bad">{{ __('Complet') }}</span>@endif</div>
  </div>
</a>

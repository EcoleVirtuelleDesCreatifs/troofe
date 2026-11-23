@props(['dish'])
<a class="card-x" href="{{ route('dishes.show', $dish) }}">
  <div class="cover"><img src="{{ $dish->imageUrl() }}" alt="{{ $dish->name }}" loading="lazy" decoding="async" width="800" height="500"><div class="tag-float"><span class="tag">{{ __($dish->origin_country) }}</span></div></div>
  <div class="body"><h3>{{ $dish->name }}</h3><p class="small muted mb-0 cu-clamp">{{ $dish->tr('summary') }}</p>
    <div class="meta">@if ($dish->region)<span>{{ $dish->region }}</span>@endif @if ($dish->prep_minutes)<span><i class="bi bi-clock"></i> {{ $dish->prep_minutes }} min</span>@endif</div></div>
</a>

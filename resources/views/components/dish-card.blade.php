@props(['dish'])
@php $n = $dish->restaurants_count ?? $dish->restaurants()->count(); @endphp
<a class="card-x" href="{{ route('dishes.show', $dish) }}">
  <div class="cover"><img src="{{ $dish->imageUrl() }}" alt="{{ $dish->name }}" loading="lazy" decoding="async" width="800" height="500"><div class="tag-float"><span class="tag">{{ __($dish->category) }}</span></div>
    <x-fav-button type="dish" :model="$dish" />
  </div>
  <div class="body"><h3>{{ $dish->name }}</h3>
    <div class="meta"><span><i class="bi bi-globe-europe-africa"></i> {{ $dish->origin_country }}</span></div>
    <div class="small muted">{{ trans_choice('{1} :count restaurant le propose|[2,*] :count restaurants le proposent', $n) }}</div></div>
</a>

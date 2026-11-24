@props(['restaurant' => null, 'r' => null, 'fallbackImage' => null])
@php $r = $r ?? $restaurant; $avg = $r->rating ?? $r->ratingAverage(); $open = $r->isOpenNow(); $ambience = ! $r->cover && $fallbackImage; @endphp
<a class="card-x" href="{{ route('restaurants.show', $r) }}">
  <div class="cover"><img src="{{ $ambience ? $fallbackImage : $r->coverUrl() }}" alt="{{ $ambience ? __('Photo d’ambiance de restauration') : $r->name }}" loading="lazy" decoding="async" width="800" height="500">
    <div class="tag-float">@if ($r->is_featured)<span class="tag spon">{{ __('Sponsorisé') }}</span>@endif<span class="tag">{{ __($r->cuisine) }}</span></div>
    <x-fav-button type="restaurant" :model="$r" />
  </div>
  <div class="body"><div class="d-flex justify-content-between gap-2 align-items-start"><h3>{{ $r->name }}@if ($r->is_verified) <i class="bi bi-patch-check-fill cu-verif" title="{{ __('Vérifié par Trôofê') }}"></i>@endif</h3><span class="small tnum">{{ $r->priceLabel() }}</span></div>
    <div class="meta"><span><i class="bi bi-geo-alt"></i> {{ $r->district ? $r->district.', ' : '' }}{{ $r->city }}</span>@if ($avg)<span><x-stars :value="$avg" /> <span class="tnum">{{ number_format($avg, 1, ',', '') }}</span></span>@endif</div>
    <div class="card-svc"><span class="{{ $open ? 'is-open' : '' }}">{{ $open ? __('Ouvert') : __('Fermé') }}</span>@if ($r->delivery)<span>{{ __('Livraison') }}</span>@endif @if ($r->accepts_orders)<span>{{ __('Retrait') }}</span>@endif</div></div>
</a>

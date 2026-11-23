@props(['restaurant' => null, 'r' => null])
@php $r = $r ?? $restaurant; $avg = $r->rating ?? $r->ratingAverage(); @endphp
<a class="card-x" href="{{ route('restaurants.show', $r) }}">
  <div class="cover"><img src="{{ $r->coverUrl() }}" alt="" loading="lazy">
    <div class="tag-float">@if ($r->is_featured)<span class="tag spon">{{ __('Sélection') }}</span>@endif<span class="tag">{{ __($r->cuisine) }}</span></div>
    <x-fav-button type="restaurant" :model="$r" />
  </div>
  <div class="body">
    <h3>{{ $r->name }}@if ($r->is_verified) <i class="bi bi-patch-check-fill cu-verif" title="{{ __('Vérifié par Trôofê') }}"></i>@endif</h3>
    @if ($r->tr('tagline'))<p class="small muted mb-0 cu-clamp">{{ $r->tr('tagline') }}</p>@endif
    <div class="meta"><span><i class="bi bi-geo-alt"></i> {{ $r->district ? $r->district.', ' : '' }}{{ $r->city }}</span><span>{{ $r->priceLabel() }}</span>@if ($avg)<span><x-stars :value="$avg" /> <span class="tnum">{{ number_format($avg, 1, ',', '') }}</span></span>@endif</div>
    @if ($r->accepts_orders)<div class="cu-foot"><span class="small"><i class="bi bi-bag-check"></i> {{ $r->delivery ? __('Commande et livraison') : __('Commande à emporter') }}</span></div>@endif
  </div>
</a>

@props(['talent'])
@php $t = $talent; $p = $t->min_price ?? $t->offers->min('price'); $avg = $t->rating ?? null; @endphp
<a class="card-x cu-card" href="{{ $t->url() }}">
  <div class="cover"><img src="{{ $t->coverUrl() }}" alt="" loading="lazy">
    <div class="tag-float">@if ($t->is_featured)<span class="tag spon">{{ __('Sélection') }}</span>@endif<span class="tag">{{ __($t->category) }}</span></div>
    <x-fav-button type="talent" :model="$t" />
  </div>
  <div class="body">
    <h3>{{ $t->name }}@if ($t->is_verified) <i class="bi bi-patch-check-fill cu-verif" title="{{ __('Profil vérifié par Trôofê') }}"></i>@endif</h3>
    <p class="small muted mb-0 cu-clamp">{{ $t->tr('tagline') }}</p>
    <div class="meta"><span><i class="bi bi-geo-alt"></i> {{ $t->city }}, {{ __($t->country) }}</span>@if ($avg)<span><x-stars :value="$avg" /> <span class="tnum">{{ number_format($avg, 1, ',', '') }}</span></span>@endif</div>
    <div class="cu-foot"><span>@if ($p)<span>{{ __('Dès') }}</span> <b class="tnum">{{ money($p) }}</b>@else{{ __('Sur devis') }}@endif</span>
      <span class="small muted">@if ($t->type === 'troupe'){{ __(':n artistes', ['n' => $t->members + $t->musicians]) }}@elseif ($t->type === 'artiste'){{ $t->members > 1 ? __(':n artistes', ['n' => $t->members]) : __('Artiste solo') }}@else{{ $t->visit_price !== null ? __('Visite d\'atelier') : trans_choice(':count création|:count créations', $t->offers->count()) }}@endif</span></div>
  </div>
</a>

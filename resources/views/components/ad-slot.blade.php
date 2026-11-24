@props(['code', 'fallbackImage' => null])
@php
  $campaign = \App\Models\AdCampaign::live()->whereHas('slot', fn ($q) => $q->where('code', $code)->where('is_active', true))->with('slot')->inRandomOrder()->first();
  if ($campaign) { \App\Models\AdCampaign::whereKey($campaign->id)->increment('impressions'); }
  $colors = ['brun' => ['#5A1E0E', '#F6EEE6', '#E2A65A'], 'ocre' => ['#B5802A', '#1E0D06', '#5A1E0E'], 'vert' => ['#2F5A3A', '#F1E2C5', '#E7C17A'], 'nuit' => ['#1F4E5A', '#F3E6CF', '#E7B04F']];
@endphp
@if ($campaign)
  @php [$bg, $fg, $ac] = $colors[$campaign->color] ?? $colors['brun']; $img = $campaign->image ? \Illuminate\Support\Facades\Storage::disk('public')->url($campaign->image) : ($fallbackImage ?: media_url(null, 'article', 'ad'.$campaign->id)); @endphp
  @if ($campaign->slot->format === 'native')
    <a class="card-x ad-native" href="{{ route('ads.click', $campaign) }}" target="_blank" rel="noopener sponsored nofollow" style="--ad-bg:{{ $bg }};--ad-fg:{{ $fg }};--ad-ac:{{ $ac }}">
      <div class="cover"><img src="{{ $img }}" alt=""><div class="tag-float"><span class="tag spon">{{ __('Sponsorisé') }}</span></div></div>
      <div class="body"><div class="small muted">{{ $campaign->advertiser }}</div><h3>{{ $campaign->title }}</h3><p class="small muted mb-2">{{ $campaign->text }}</p><span class="ad-cta-inline mt-auto">{{ $campaign->cta }} <i class="bi bi-arrow-right"></i></span></div>
    </a>
  @else
    <a class="ad {{ $campaign->slot->format === 'rectangle' ? 'ad-rect' : 'ad-leader' }}" href="{{ route('ads.click', $campaign) }}" target="_blank" rel="noopener sponsored nofollow" style="--ad-bg:{{ $bg }};--ad-fg:{{ $fg }};--ad-ac:{{ $ac }}">
      <span class="ad-label">{{ __('Publicité') }} · {{ $campaign->advertiser }}</span><div class="ad-art"><img src="{{ $img }}" alt=""></div><div class="ad-txt"><b>{{ $campaign->title }}</b><span>{{ $campaign->text }}</span></div><span class="ad-cta">{{ $campaign->cta }} <i class="bi bi-arrow-right"></i></span>
    </a>
  @endif
@endif

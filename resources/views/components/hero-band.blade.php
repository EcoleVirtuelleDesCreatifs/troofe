@props(['img', 'eyebrow' => null, 'title', 'lead' => null, 'stats' => [], 'short' => false, 'verified' => false])
<section class="ent-hero {{ $short ? 'short' : '' }}">
  <img src="{{ $img }}" alt="{{ $title }}" width="1600" height="1000" fetchpriority="high" decoding="async">
  <div class="ent-shade"></div>
  <div class="wrap gutter ent-in">
    @if ($eyebrow)<div class="eyebrow">{{ $eyebrow }}</div>@endif
    <h1 class="display">{{ $title }}@if ($verified) <i class="bi bi-patch-check-fill cu-verif" title="{{ __('Vérifié par Trôofê') }}"></i>@endif</h1>
    @if ($lead)<p class="lead">{{ $lead }}</p>@endif
    @if ($stats)<div class="ent-stats">@foreach ($stats as [$v, $l])<div><b>{{ $v }}</b><span>{{ $l }}</span></div>@endforeach</div>@endif
    {{ $slot }}
  </div>
</section>

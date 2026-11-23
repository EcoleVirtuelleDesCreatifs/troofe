@if (seo()->faq)
<section class="section pt-0" aria-labelledby="faq-title"><div class="wrap gutter">
  <div class="panel faq-block">
    <h2 class="h4" id="faq-title">{{ __('Questions fréquentes') }}</h2>
    @foreach (seo()->faq as $i => [$q, $a])
      <details class="faq-item" @if ($i === 0) open @endif><summary>{{ $q }}</summary><p class="mb-0">{{ $a }}</p></details>
    @endforeach
  </div>
</div></section>
@endif

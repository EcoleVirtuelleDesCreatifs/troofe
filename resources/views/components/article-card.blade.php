@props(['article', 'big' => false])
<a class="card-x" href="{{ route('articles.show', $article) }}">
  <div class="cover"@if ($big) style="aspect-ratio:16/9"@endif><img src="{{ $article->coverUrl() }}" alt="{{ $article->tr('title') }}" loading="lazy" decoding="async" width="800" height="500"></div>
  <div class="body"><div class="eyebrow">{{ __($article->category) }}</div><h3 @if ($big) style="font-size:1.4rem"@endif>{{ $article->tr('title') }}</h3><p class="muted small mb-0">{{ $article->tr('excerpt') }}</p><div class="meta mt-auto pt-2"><span>{{ $article->author?->name ?? 'Trôofê' }}</span><span>{{ date_human($article->published_at, 'D MMM YYYY') }}</span></div></div>
</a>

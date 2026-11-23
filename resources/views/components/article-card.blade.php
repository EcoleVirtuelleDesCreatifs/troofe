@props(['article'])
<a class="card-x" href="{{ route('articles.show', $article) }}">
  <div class="cover"><img src="{{ $article->coverUrl() }}" alt="{{ $article->tr('title') }}" loading="lazy" decoding="async" width="800" height="500"><div class="tag-float"><span class="tag">{{ __($article->category) }}</span></div></div>
  <div class="body"><h3>{{ $article->tr('title') }}</h3><p class="small muted mb-0 cu-clamp">{{ $article->tr('excerpt') }}</p>
    <div class="meta"><span>{{ date_human($article->published_at, 'D MMM YYYY') }}</span><span>{{ trans_choice(':count min de lecture|:count min de lecture', $article->readingMinutes()) }}</span></div></div>
</a>

@extends('layouts.app', ['title' => $article->tr('title'), 'description' => $article->tr('excerpt'), 'ogImage' => $article->coverUrl()])
@section('content')
@php
  $body = $article->tr('body') ?? '';
  $parts = preg_split('/<\/p>\s*/', $body, -1, PREG_SPLIT_NO_EMPTY);
@endphp
<section class="section"><div class="wrap gutter">
  @include('partials.crumbs', ['items' => [__('Actualités') => route('articles.index'), __($article->category) => null]])
  <article class="mx-auto" style="max-width:820px"><div class="eyebrow">{{ __($article->category) }}</div><h1 class="display mt-2" style="font-size:clamp(1.9rem,4vw,2.9rem)">{{ $article->tr('title') }}</h1>
  <p class="lead muted">{{ $article->tr('excerpt') }}</p>
  <div class="d-flex align-items-center gap-3 mb-4 flex-wrap"><x-avatar :user="$article->author" :size="38" /><div class="small"><b>{{ $article->author?->name ?? 'Trôofê' }}</b><br><span class="muted">{{ date_human($article->published_at, 'D MMMM YYYY') }} · {{ trans_choice(':count min de lecture|:count min de lecture', $article->readingMinutes()) }}</span></div><button class="btn btn-ghost btn-sm ms-auto" data-share><i class="bi bi-link-45deg"></i> {{ __('Copier le lien') }}</button></div>
  <div class="detail-hero mb-4"><img src="{{ $article->coverUrl() }}" alt="{{ $article->tr('title') }}"></div>
  @if (app()->getLocale() === 'en' && ! $article->body_en)<div class="notice info mb-3"><i class="bi bi-translate"></i><div>{{ __('This article is only available in French for now.') }}</div></div>@endif
  <div class="prose">
    @if (count($parts) > 2)
      @foreach ($parts as $i => $p){!! $p !!}</p>@if ($i === 1)<div class="my-4"><x-ad-slot code="article_inline" /></div>@endif @endforeach
    @else
      {!! $body !!}
    @endif
  </div>
  <div class="mt-4" style="max-width:340px"><x-ad-slot code="article_side" /></div>
  </article>
  @if ($more->isNotEmpty())<div class="mt-5"><h2 class="h4 mb-3">{{ __('À lire aussi') }}</h2><div class="grid g3">@foreach ($more as $a)<x-article-card :article="$a" />@endforeach</div></div>@endif
</div></section>
@endsection

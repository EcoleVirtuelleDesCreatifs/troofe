@extends('layouts.app', ['title' => $article->tr('title'), 'description' => $article->tr('excerpt'), 'ogImage' => $article->coverUrl()])
@section('content')
<x-hero-band :img="$article->coverUrl()" :eyebrow="__($article->category).' · '.date_human($article->published_at, 'D MMMM YYYY')" :title="$article->tr('title')" :lead="$article->tr('excerpt')" short />
<section class="section"><div class="wrap gutter">
  @include('partials.crumbs', ['items' => [__('Actualités') => route('articles.index'), \Illuminate\Support\Str::limit($article->tr('title'), 50) => null]])
  <div class="two-col"><article>
    <p class="small muted">{{ __('Par :author', ['author' => $article->author?->name ?? 'Trôofê']) }} · {{ trans_choice(':count min de lecture|:count min de lecture', $article->readingMinutes()) }}</p>
    @if (app()->getLocale() === 'en' && ! $article->body_en)<div class="notice info mb-3"><i class="bi bi-translate"></i><div>{{ __('This article is only available in French for now.') }}</div></div>@endif
    <div class="prose">{!! $article->tr('body') !!}</div>
  </article>
  <aside class="sticky-side d-grid gap-3"><x-ad-slot code="article_side" />
    <div class="panel"><h3 class="h6">{{ __('La lettre Trôofê') }}</h3><p class="small muted">{{ __('Le meilleur de la semaine, chaque jeudi.') }}</p>
      <form method="post" action="{{ route('newsletter.subscribe') }}">@csrf<input type="email" name="email" required class="form-control form-control-sm mb-2" placeholder="{{ __('votre@email.com') }}"><input type="text" name="website" class="d-none" tabindex="-1" autocomplete="off"><button class="btn btn-brand btn-sm w-100">{{ __('S\'inscrire') }}</button></form></div>
  </aside></div>
  @if ($more->isNotEmpty())<div class="mt-5"><div class="sh"><h2>{{ __('À lire aussi') }}</h2></div><div class="grid g3">@foreach ($more as $a)<x-article-card :article="$a" />@endforeach</div></div>@endif
</div></section>
@endsection

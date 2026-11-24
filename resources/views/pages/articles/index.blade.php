@extends('layouts.app', ['title' => __('Actualités')])
@section('content')
<section class="section"><div class="wrap gutter">
  @include('partials.crumbs', ['items' => [__('Actualités') => null]])
  <div class="section-head"><div><h1 class="h2 mb-1">{{ __('Actualités') }}</h1><p class="muted mb-0">{{ __('Annonces, portraits de chefs, tendances, recettes et histoires communautaires.') }}</p></div>
    <form method="get"><input type="hidden" name="rubrique" value="{{ request('rubrique') }}"><input class="form-control" style="max-width:280px" name="q" placeholder="{{ __('Rechercher un article') }}" value="{{ request('q') }}" aria-label="{{ __('Rechercher un article') }}"></form></div>
  <div class="chip-row mb-4"><a class="chip {{ request('rubrique') ? '' : 'on' }}" href="{{ route('articles.index', array_filter(['q' => request('q')])) }}">{{ __('Tout') }}</a>@foreach ($categories as $c)<a class="chip {{ request('rubrique') === $c ? 'on' : '' }}" href="{{ route('articles.index', array_filter(['rubrique' => $c, 'q' => request('q')])) }}">{{ __($c) }}</a>@endforeach</div>
  @if ($articles->isEmpty())<x-empty icon="bi-newspaper" :text="__('Aucun article.')" />@else
    @php $side = $articles->slice(1, 4); @endphp
    <div class="row g-4"><div class="{{ $side->isNotEmpty() ? 'col-lg-8' : 'col-12' }}"><x-article-card :article="$articles[0]" big /></div>
      @if ($side->isNotEmpty())<div class="col-lg-4"><div class="panel h-100"><h2 class="h6 eyebrow mb-3">{{ __('À lire aussi') }}</h2><div class="news-mini-list">
        @foreach ($side as $a)<a class="news-mini" href="{{ route('articles.show', $a) }}"><img src="{{ $a->coverUrl() }}" alt="" loading="lazy" decoding="async" width="192" height="120"><div class="min-w-0"><div class="eyebrow">{{ __($a->category) }}</div><h3 class="news-mini-t">{{ $a->tr('title') }}</h3><div class="small muted">{{ date_human($a->published_at, 'D MMM YYYY') }}</div></div></a>@endforeach
      </div></div></div>@endif
    </div>
    @if ($articles->count() > 4)<div class="section-head mt-5"><div><h2 class="h4 mb-0">{{ __('Tous les articles') }}</h2></div><span class="muted small">{{ trans_choice(':count article|:count articles', $articles->total()) }}</span></div><div class="grid g3">@foreach ($articles->slice(4) as $a)<x-article-card :article="$a" />@endforeach</div>@endif
    <div class="mt-4">{{ $articles->links() }}</div>
  @endif
</div></section>
@endsection

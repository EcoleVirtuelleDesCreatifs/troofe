@extends('layouts.app', ['title' => __('Actualités')])
@section('content')
<section class="section"><div class="wrap gutter">
  @include('partials.crumbs', ['items' => [__('Actualités') => null]])
  <div class="sh"><div><div class="eyebrow">{{ __('Le magazine') }}</div><h1 class="h2">{{ __('Actualités') }}</h1></div></div>
  <div class="chip-row mb-4"><a class="chip {{ request('rubrique') ? '' : 'on' }}" href="{{ route('articles.index') }}">{{ __('Tout') }}</a>@foreach ($categories as $c)<a class="chip {{ request('rubrique') === $c ? 'on' : '' }}" href="{{ route('articles.index', ['rubrique' => $c]) }}">{{ __($c) }}</a>@endforeach</div>
  @if ($articles->isEmpty())<x-empty icon="bi-newspaper" :text="__('Aucun article pour le moment.')" />@else
  <div class="grid g3">@foreach ($articles as $a)<x-article-card :article="$a" />@endforeach</div><div class="mt-4">{{ $articles->links() }}</div>@endif
</div></section>
@endsection

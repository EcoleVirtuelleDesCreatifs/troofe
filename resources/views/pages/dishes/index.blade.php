@extends('layouts.app', ['title' => __('Encyclopédie culinaire')])
@section('content')
<section class="section"><div class="wrap gutter">
  @include('partials.crumbs', ['items' => [__('Encyclopédie') => null]])
  <div class="section-head"><div><h1 class="h2 mb-1">{{ __('Encyclopédie culinaire') }}</h1><p class="muted mb-0">{{ __('Origines, ingrédients, préparation, et où les goûter. Les origines partagées entre plusieurs pays sont présentées comme telles.') }}</p></div></div>
  <form class="row g-2 mb-3" method="get">
    <div class="col-md-5"><input class="form-control" name="q" placeholder="{{ __('Rechercher un plat ou un ingrédient (ex. : arachide)') }}" value="{{ request('q') }}" aria-label="{{ __('Rechercher un plat') }}"></div>
    <div class="col-6 col-md-3"><select class="form-select" name="pays" aria-label="{{ __('Pays') }}"><option value="">{{ __('Tous les pays') }}</option>@foreach ($countries as $c)<option @selected(request('pays') === $c)>{{ $c }}</option>@endforeach</select></div>
    <div class="col-6 col-md-3"><select class="form-select" name="famille" aria-label="{{ __('Famille') }}"><option value="">{{ __('Toutes les familles') }}</option>@foreach ($categories as $c)<option @selected(request('famille') === $c)>{{ __($c) }}</option>@endforeach</select></div>
    <div class="col-md-1"><button class="btn btn-brand w-100" aria-label="{{ __('Filtrer') }}"><i class="bi bi-funnel"></i></button></div>
  </form>
  <div class="chip-row mb-4"><a class="chip {{ request('pays') ? '' : 'on' }}" href="{{ route('dishes.index', array_filter(['q' => request('q'), 'famille' => request('famille')])) }}">{{ __('Tout') }}</a>@foreach ($countries as $c)<a class="chip {{ request('pays') === $c ? 'on' : '' }}" href="{{ route('dishes.index', array_filter(['pays' => $c, 'q' => request('q'), 'famille' => request('famille')])) }}">{{ $c }}</a>@endforeach</div>
  @if ($dishes->isEmpty())<x-empty icon="bi-book" :text="__('Aucun plat ne correspond. Essayez un autre pays ou retirez le filtre famille.')" />@else
  <div class="grid">@foreach ($dishes as $d)<x-dish-card :dish="$d" />@endforeach</div><div class="mt-4">{{ $dishes->links() }}</div>@endif
</div></section>
@endsection

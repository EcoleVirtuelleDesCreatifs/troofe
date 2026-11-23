@extends('layouts.app', ['title' => __('Encyclopédie culinaire')])
@section('content')
<section class="section"><div class="wrap gutter">
  @include('partials.crumbs', ['items' => [__('Encyclopédie') => null]])
  <div class="sh"><div><div class="eyebrow">{{ __('Encyclopédie') }}</div><h1 class="h2">{{ __('Les plats d\'Afrique, leur histoire, leurs recettes') }}</h1></div></div>
  <form class="panel mb-4 row g-2 align-items-end" method="get">
    <div class="col-md-6"><label class="form-label" for="q">{{ __('Rechercher un plat') }}</label><input class="form-control" id="q" name="q" value="{{ request('q') }}"></div>
    <div class="col-md-4"><label class="form-label" for="pays">{{ __('Pays') }}</label><select class="form-select" id="pays" name="pays"><option value="">{{ __('Tous') }}</option>@foreach ($countries as $c)<option @selected(request('pays') === $c)>{{ $c }}</option>@endforeach</select></div>
    <div class="col-md-2"><button class="btn btn-brand w-100">{{ __('Filtrer') }}</button></div>
  </form>
  @if ($dishes->isEmpty())<x-empty icon="bi-book" :text="__('Aucun plat trouvé.')" />@else
  <div class="grid">@foreach ($dishes as $d)<x-dish-card :dish="$d" />@endforeach</div><div class="mt-4">{{ $dishes->links() }}</div>@endif
</div></section>
@endsection

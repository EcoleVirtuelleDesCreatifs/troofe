@extends('layouts.app', ['title' => __('Recherche')])
@section('content')
<section class="section"><div class="wrap gutter" style="max-width:860px">
  <form class="d-flex gap-2 mb-4"><input class="form-control form-control-lg" name="q" value="{{ $q }}" placeholder="{{ __('Restaurant, plat, artiste, artisan, événement…') }}" aria-label="{{ __('Rechercher') }}" autofocus><button class="btn btn-brand btn-lg"><i class="bi bi-search"></i></button></form>
  @if ($q && $results->isEmpty())<x-empty icon="bi-search" :text="__('Aucun résultat pour « :q ».', ['q' => $q])" />@endif
  <div class="pal-res">@foreach ($results as $group => $items)<div class="pal-group">{{ $group }}</div>@foreach ($items as $i)<a href="{{ $i['url'] }}"><img src="{{ $i['img'] }}" alt=""><span><b class="d-block">{{ $i['title'] }}</b><span class="small muted">{{ $i['sub'] }}</span></span></a>@endforeach @endforeach</div>
</div></section>
@endsection

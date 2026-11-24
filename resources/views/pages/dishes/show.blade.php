@extends('layouts.app', ['title' => $dish->name, 'description' => $dish->tr('summary'), 'ogImage' => $dish->imageUrl()])
@section('content')
<section class="section"><div class="wrap gutter">
  @include('partials.crumbs', ['items' => [__('Encyclopédie') => route('dishes.index'), $dish->name => null]])
  <div class="row g-4 align-items-center mb-4"><div class="col-lg-6"><div class="eyebrow">{{ __($dish->category) }}</div><h1 class="display mt-1" style="font-size:clamp(2rem,4.5vw,3.2rem)">{{ $dish->name }}</h1>
    <p class="lead muted">{{ $dish->tr('summary') }}</p><div class="d-flex gap-2 flex-wrap"><span class="pill brand nodot"><i class="bi bi-geo"></i> {{ __($dish->origin_country) }}</span>@if ($dish->region)<span class="pill nodot"><i class="bi bi-geo-alt"></i> {{ $dish->region }}</span>@endif</div>
    <div class="d-flex gap-2 mt-3"><x-fav-button type="dish" :model="$dish" look="btn btn-ghost"> {{ __('Enregistrer') }}</x-fav-button><button class="btn btn-ghost" data-share><i class="bi bi-link-45deg"></i> {{ __('Copier le lien') }}</button></div></div>
    <div class="col-lg-6"><div class="detail-hero" style="aspect-ratio:4/3"><img src="{{ $dish->imageUrl() }}" alt="{{ $dish->name }}"></div></div></div>
  <div class="two-col"><div>
    @if ($dish->tr('history'))<div class="panel mb-3"><h2 class="h5">{{ __('Origines') }}</h2><div class="prose mb-0">{!! $dish->tr('history') !!}</div></div>@endif
    @if ($dish->ingredients)<div class="panel mb-3"><h2 class="h5">{{ __('Ingrédients') }}</h2><div class="chip-row">@foreach ($dish->ingredients as $i)<span class="chip" style="cursor:default">{{ $i }}</span>@endforeach</div></div>@endif
    @if ($dish->tr('recipe'))<div class="panel mb-3"><h2 class="h5">{{ __('Préparation') }}</h2><div class="prose mb-0">{!! $dish->tr('recipe') !!}</div>@endif
    @if (app()->getLocale() === 'en' && ! $dish->history_en && $dish->history)<p class="small muted"><i class="bi bi-translate"></i> {{ __('Cette fiche n\'est pas encore traduite en anglais.') }}</p>@endif
    @if ($craftsmen->isNotEmpty())<div class="section-head"><h2 class="h4 mb-0">{{ __('Le savoir-faire derrière le plat') }}</h2></div><div class="grid g3 mb-4">@foreach ($craftsmen as $t)<x-talent-card :talent="$t" />@endforeach</div>@endif
  </div>
  <aside class="sticky-side"><div class="panel"><h2 class="h6">{{ __('Où le goûter ?') }}</h2>
    @forelse ($restaurants as $r)<a href="{{ route('restaurants.show', $r) }}" class="d-flex justify-content-between gap-2 py-2 border-bottom" style="border-color:var(--line)!important;color:var(--ink)"><span><b>{{ $r->name }}</b><br><span class="small muted">{{ $r->district }}, {{ $r->city }}</span></span><img src="{{ $r->coverUrl() }}" alt="" style="width:44px;height:44px;border-radius:8px;object-fit:cover"></a>
    @empty<p class="small muted mb-0">{{ __('Aucun restaurant partenaire ne le propose pour l\'instant.') }}</p>@endforelse
  </div><div class="mt-3"><x-ad-slot code="plat_side" /></div></aside></div>
  @if ($related->isNotEmpty())<div class="mt-5"><div class="sh"><h2>{{ __('Du même pays') }}</h2></div><div class="grid">@foreach ($related as $d)<x-dish-card :dish="$d" />@endforeach</div></div>@endif
</div></section>
@endsection

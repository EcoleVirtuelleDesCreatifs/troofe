@extends('layouts.app', ['title' => $dish->name, 'description' => $dish->tr('summary'), 'ogImage' => $dish->imageUrl()])
@section('content')
<x-hero-band :img="$dish->imageUrl()" :eyebrow="__('Encyclopédie').' · '.__($dish->origin_country)" :title="$dish->name" :lead="$dish->tr('summary')"
  :stats="array_values(array_filter([[__($dish->origin_country), __('origine')], $dish->region ? [$dish->region, __('région')] : null, $dish->prep_minutes ? [$dish->prep_minutes.' min', __('préparation')] : null]))" />
<section class="section"><div class="wrap gutter">
  @include('partials.crumbs', ['items' => [__('Encyclopédie') => route('dishes.index'), $dish->name => null]])
  <div class="two-col"><article>
    @if ($dish->tr('history'))<h2 class="h4">{{ __('Histoire') }}</h2><div class="prose mb-4">{!! $dish->tr('history') !!}</div>@endif
    @if ($dish->ingredients)<div class="panel mb-4"><h2 class="h5">{{ __('Ingrédients') }}</h2><ul class="mb-0">@foreach ($dish->ingredients as $i)<li>{{ $i }}</li>@endforeach</ul></div>@endif
    @if ($dish->tr('recipe'))<h2 class="h4">{{ __('Recette') }}</h2><div class="prose mb-4">{!! $dish->tr('recipe') !!}</div>@endif
    @if (app()->getLocale() === 'en' && ! $dish->history_en && $dish->history)<p class="small muted"><i class="bi bi-translate"></i> {{ __('Cette fiche n\'est pas encore traduite en anglais.') }}</p>@endif
    @if ($craftsmen->isNotEmpty())<div class="section-head"><h2 class="h4 mb-0">{{ __('Le savoir-faire derrière le plat') }}</h2></div><div class="grid g3 mb-4">@foreach ($craftsmen as $t)<x-talent-card :talent="$t" />@endforeach</div>@endif
  </article>
  <aside class="sticky-side d-grid gap-3">
    @if ($restaurants->isNotEmpty())<div class="panel"><h3 class="h6">{{ __('Où le goûter') }}</h3>@foreach ($restaurants as $r)<a href="{{ route('restaurants.show', $r) }}" class="d-flex gap-2 align-items-center py-2"><img src="{{ $r->coverUrl() }}" alt="" style="width:44px;height:44px;border-radius:8px;object-fit:cover"><span><b class="d-block">{{ $r->name }}</b><span class="small muted">{{ $r->district }}, {{ $r->city }}</span></span></a>@endforeach</div>@endif
    <x-ad-slot code="plat_side" />
  </aside></div>
  @if ($related->isNotEmpty())<div class="mt-5"><div class="sh"><h2>{{ __('Du même pays') }}</h2></div><div class="grid">@foreach ($related as $d)<x-dish-card :dish="$d" />@endforeach</div></div>@endif
</div></section>
@endsection

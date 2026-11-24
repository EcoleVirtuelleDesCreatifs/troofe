@extends('layouts.app', ['title' => __($meta['plural'])])
@section('content')
@php $top = $talents->first(); @endphp
<x-hero-band short :img="$top?->coverUrl('band') ?? media_url(null, $type === 'troupe' ? 'danse' : 'tissage', $type)" :eyebrow="__('Culture & savoir-faire')" :title="__($meta['plural'])" :lead="__($meta['lead'])"
  :stats="[[$stats['count'], $type === 'artisan' ? __('ateliers') : __('fiches')], [$stats['cities'], __('villes')], [$stats['verified'], __('profils vérifiés')], [$stats['min'] ? money($stats['min']) : '—', __('à partir de')]]" />
<section class="section"><div class="wrap gutter">
  @include('partials.crumbs', ['items' => [__('Culture') => route('culture.hub'), __($meta['plural']) => null]])
  @include('culture._switch', ['current' => $type])
  <form class="panel mb-4 row g-2 align-items-end" method="get">
    <div class="col-lg-3"><label class="form-label" for="cf-q">{{ $type === 'artisan' ? __('Objet ou matière') : __('Nom, style ou répertoire') }}</label><input class="form-control" id="cf-q" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="{{ $type === 'troupe' ? __('Ex. : zaouli, coupé-décalé') : ($type === 'artiste' ? __('Ex. : kora, DJ, conte') : __('Ex. : canari, kente, panier')) }}"></div>
    <div class="col-6 col-lg-2"><label class="form-label" for="cf-c">{{ __($meta['category']) }}</label><select class="form-select" id="cf-c" name="categorie"><option value="">{{ __('Tous') }}</option>@foreach ($categories as $c)<option @selected(($filters['categorie'] ?? '') === $c)>{{ $c }}</option>@endforeach</select></div>
    <div class="col-6 col-lg-2"><label class="form-label" for="cf-v">{{ __('Ville') }}</label><select class="form-select" id="cf-v" name="ville"><option value="">{{ __('Toutes') }}</option>@foreach ($cities as $c)<option @selected(($filters['ville'] ?? '') === $c)>{{ $c }}</option>@endforeach</select></div>
    @if ($type !== 'artisan')<div class="col-6 col-lg-2"><label class="form-label" for="cf-o">{{ __('Occasion') }}</label><select class="form-select" id="cf-o" name="occasion"><option value="">{{ __('Toutes') }}</option>@foreach ($occasions as $c)<option value="{{ $c }}" @selected(($filters['occasion'] ?? '') === $c)>{{ __($c) }}</option>@endforeach</select></div>@endif
    <div class="col-6 col-lg-{{ $type !== 'artisan' ? 3 : 5 }}"><label class="form-label" for="cf-b">{{ __('Budget') }}</label><select class="form-select" id="cf-b" name="budget"><option value="">{{ __('Tous les budgets') }}</option>@foreach ($type === 'artisan' ? [15000, 30000, 60000] : [100000, 250000, 500000] as $v)<option value="{{ $v }}" @selected(($filters['budget'] ?? '') == $v)>{{ __('Dès moins de :p', ['p' => money($v)]) }}</option>@endforeach</select></div>
    <div class="col-12 d-flex flex-wrap gap-3 align-items-center mt-2">
      <div class="form-check form-switch m-0"><input class="form-check-input" type="checkbox" id="cf-ver" name="verifie" value="1" @checked($filters['verifie'] ?? false)><label class="form-check-label" for="cf-ver">{{ __('Profils vérifiés uniquement') }}</label></div>
      <div class="ms-auto d-flex align-items-center gap-2"><label class="small muted" for="cf-sort">{{ __('Trier') }}</label><select class="form-select form-select-sm" id="cf-sort" name="tri" style="width:auto"><option value="pertinence">{{ __('Pertinence') }}</option><option value="note" @selected(($filters['tri'] ?? '') === 'note')>{{ __('Mieux notés') }}</option><option value="prix" @selected(($filters['tri'] ?? '') === 'prix')>{{ __('Prix croissant') }}</option><option value="nom" @selected(($filters['tri'] ?? '') === 'nom')>{{ __('Nom A→Z') }}</option></select>
        <a href="{{ route('culture.index', $meta['route']) }}" class="btn btn-ghost btn-sm">{{ __('Réinitialiser') }}</a><button class="btn btn-brand btn-sm">{{ __('Filtrer') }}</button></div>
    </div>
  </form>
  <x-ad-slot code="culture_leader" />
  <div class="d-flex justify-content-between mb-3 mt-4"><span class="muted small">{{ trans_choice(':count résultat|:count résultats', $talents->total()) }}</span><a class="small" href="{{ route('partner') }}"><i class="bi bi-plus-circle"></i> {{ $type === 'artisan' ? __('Référencer mon atelier') : ($type === 'troupe' ? __('Référencer mon groupe') : __('Référencer mon activité')) }}</a></div>
  @if ($talents->isEmpty())<x-empty icon="bi-search" :text="__('Aucune fiche ne correspond. Retirez un filtre ou essayez une autre ville.')" />@else
  <div class="grid">@foreach ($talents as $i => $t)<x-talent-card :talent="$t" />@if ($i === 1)<x-ad-slot code="culture_native" />@endif @endforeach</div>
  <div class="mt-4">{{ $talents->links() }}</div>@endif
</div></section>
@endsection

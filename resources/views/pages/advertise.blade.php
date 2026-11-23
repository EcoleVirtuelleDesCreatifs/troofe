@extends('layouts.app', ['title' => __('Annoncer sur Trôofê')])
@section('content')
<x-hero-band short :img="media_url(null, 'article', 'ads')" :eyebrow="__('Régie publicitaire')" :title="__('Votre marque, à la table des gourmets.')" :lead="__('Bannières, cartes sponsorisées et encarts sur les pages restaurants, culture et agenda. Validation sous 24 h, paiement Mobile Money, bilan par e-mail.')" />
<section class="section"><div class="wrap gutter">
  <div class="row g-3 mb-5">@foreach ($slots as $s)<div class="col-md-6 col-lg-4"><div class="panel h-100"><div class="d-flex justify-content-between"><b>{{ __($s->name) }}</b><span class="pill info">{{ $s->format }}</span></div><div class="small muted">{{ __($s->page) }} · {{ $s->dimensions }}</div><div class="fs-4 fw-bold tnum mt-2">{{ money($s->price_per_day) }} <span class="fs-6 muted fw-normal">/ {{ __('jour') }}</span></div><div class="small muted">{{ trans_choice(':count campagne en cours|:count campagnes en cours', $s->live_count) }}</div></div></div>@endforeach</div>
  @auth
  <form method="post" action="{{ route('campaigns.store') }}" enctype="multipart/form-data" class="panel row g-3">@csrf
    <h2 class="h4 col-12 mb-0">{{ __('Réserver un espace') }}</h2>
    <div class="col-md-6"><label class="form-label" for="slot">{{ __('Emplacement') }}</label><select class="form-select" id="slot" name="ad_slot_id" required>@foreach ($slots as $s)<option value="{{ $s->id }}">{{ __($s->name) }} — {{ money($s->price_per_day) }}/{{ __('jour') }}</option>@endforeach</select></div>
    <div class="col-md-6"><label class="form-label" for="adv">{{ __('Annonceur') }}</label><input class="form-control" id="adv" name="advertiser" required value="{{ old('advertiser', auth()->user()->name) }}"></div>
    <div class="col-md-6"><label class="form-label" for="tt">{{ __('Titre') }}</label><input class="form-control" id="tt" name="title" required maxlength="80" value="{{ old('title') }}"></div>
    <div class="col-md-3"><label class="form-label" for="cta">{{ __('Bouton') }}</label><input class="form-control" id="cta" name="cta" required maxlength="30" value="{{ old('cta', __('Découvrir')) }}"></div>
    <div class="col-md-3"><label class="form-label" for="url">{{ __('Lien') }}</label><input type="url" class="form-control" id="url" name="url" required value="{{ old('url') }}" placeholder="https://"></div>
    <div class="col-12"><label class="form-label" for="tx">{{ __('Texte') }}</label><textarea class="form-control" id="tx" name="text" maxlength="200" rows="2">{{ old('text') }}</textarea></div>
    <div class="col-md-4"><label class="form-label" for="img">{{ __('Visuel (2 Mo max.)') }}</label><input type="file" class="form-control" id="img" name="image" accept="image/*"></div>
    <div class="col-md-4"><label class="form-label" for="from">{{ __('Début') }}</label><input type="date" class="form-control" id="from" name="starts_on" required min="{{ today()->toDateString() }}" value="{{ old('starts_on', today()->addDays(2)->toDateString()) }}"></div>
    <div class="col-md-4"><label class="form-label" for="to">{{ __('Fin') }}</label><input type="date" class="form-control" id="to" name="ends_on" required value="{{ old('ends_on', today()->addDays(16)->toDateString()) }}"></div>
    <div class="col-12 d-flex justify-content-between flex-wrap gap-2 align-items-center"><span class="small muted">{{ __('Après validation, vous recevez un lien de paiement par e-mail. Diffusion dès réception du paiement.') }}</span><button class="btn btn-brand">{{ __('Envoyer pour validation') }}</button></div>
  </form>
  @else
  <div class="panel text-center"><p>{{ __('Connectez-vous pour réserver un espace et suivre vos campagnes.') }}</p><a class="btn btn-brand" href="{{ route('login') }}">{{ __('Se connecter') }}</a></div>
  @endauth
</div></section>
@endsection

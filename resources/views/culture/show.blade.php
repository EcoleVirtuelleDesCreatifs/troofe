@extends('layouts.app', ['title' => $talent->name, 'description' => $talent->tr('tagline'), 'ogImage' => $talent->coverUrl()])
@section('content')
@php
  $t = $talent; $craft = $t->isCraft(); $p = $t->minPrice();
  $gallery = collect($t->gallery ?? [])->map(fn ($g) => \Illuminate\Support\Facades\Storage::disk('public')->url($g));
  if ($gallery->isEmpty()) { $gallery = collect(range(0, 3))->map(fn ($i) => $t->coverUrl('g'.$i)); }
  $facts = array_filter([
    [__('Basé à'), trim(($t->district ? $t->district.', ' : '').$t->city)],
    $craft && $t->maker_name ? [__('Artisan'), $t->maker_name] : null,
    ! $craft && $t->zones ? [__('Zone d\'intervention'), implode(', ', $t->zones)] : null,
    $t->type === 'troupe' ? [__('Effectif'), __(':d danseurs · :m musiciens', ['d' => $t->members, 'm' => $t->musicians])] : null,
    $t->type === 'artiste' && $t->languages ? [__('Langues'), implode(', ', $t->languages)] : null,
    $craft && $t->materials ? [__('Matières'), implode(', ', $t->materials)] : null,
    $t->since_year ? [__('Depuis'), $t->since_year] : null,
    ! $craft ? [__('Réservation'), trans_choice(':count jour à l\'avance minimum|:count jours à l\'avance minimum', $t->min_notice_days)] : null,
  ]);
@endphp
<x-hero-band :img="$t->coverUrl('hero')" :eyebrow="__($t->meta('label')).' · '.__($t->category)" :title="$t->name" :verified="$t->is_verified" :lead="$t->tr('tagline')"
  :stats="[[$t->ratingCount() ? number_format($t->ratingAverage(), 1, ',', '') : '—', $t->ratingCount() ? trans_choice('note · :count avis|note · :count avis', $t->ratingCount()) : __('pas encore d\'avis')],
           $craft ? [$t->offers->count(), __('créations au catalogue')] : [$t->type === 'troupe' ? $t->members + $t->musicians : $t->members, __('artistes')],
           [$t->since_year ?? '—', __('en activité depuis')], [$p ? money($p) : __('Sur devis'), __('à partir de')]]" />
<section class="section"><div class="wrap gutter">
  @include('partials.crumbs', ['items' => [__('Culture') => route('culture.hub'), __($t->meta('plural')) => route('culture.index', $t->meta('route')), $t->name => null]])
  @unless ($t->isPublished())<div class="notice warn mb-3"><i class="bi bi-eye-slash"></i><div>{{ __('Aperçu : cette fiche n\'est pas encore visible du public.') }}</div></div>@endunless
  <div class="two-col"><div>
    <div class="d-flex gap-2 flex-wrap mb-4">
      <a href="#booking-form" class="btn btn-brand"><i class="bi {{ $craft ? 'bi-bag' : 'bi-calendar-plus' }}"></i> {{ $craft ? __('Commander') : __('Demander une réservation') }}</a>
      <x-fav-button type="talent" :model="$t" look="btn btn-ghost"> {{ __('Favori') }}</x-fav-button>
      <x-follow-button type="talent" :model="$t" />
    </div>

    <h2 class="h4">{{ $craft ? __('L\'atelier') : __('Présentation') }}</h2>
    <p class="prose">{!! nl2br(e($t->tr('description'))) !!}</p>
    <div class="chip-row mb-4">@foreach ($t->tags ?? [] as $tag)<span class="chip">{{ $tag }}</span>@endforeach @foreach ($t->audiences ?? [] as $a)<span class="chip cu-chip-pub"><i class="bi bi-check2"></i> {{ __($a) }}</span>@endforeach</div>
    @if ($t->repertoire)<div class="panel mb-4"><h2 class="h5">{{ __('Répertoire') }}</h2><ol class="cu-rep">@foreach ($t->repertoire as $r)<li>{{ $r }}</li>@endforeach</ol></div>@endif

    @if (! $craft)
      <div class="section-head mt-2"><h2 class="h4 mb-0">{{ __('Formules') }}</h2><span class="small muted">{{ __('Tarifs indicatifs, confirmés par devis') }}</span></div>
      <div class="cu-offers mb-4">@foreach ($t->offers as $i => $o)
        <div class="cu-offer {{ $i === 1 ? 'feat' : '' }}">@if ($i === 1 && $t->offers->count() > 2)<span class="tag spon">{{ __('La plus demandée') }}</span>@endif
          <h3>{{ $o->tr('name') }}</h3><div class="small muted"><i class="bi bi-clock"></i> {{ $o->duration }}</div><div class="price tnum">{{ money($o->price) }}</div><p class="small mb-3">{{ $o->tr('details') }}</p>
          <button type="button" class="btn {{ $i === 1 ? 'btn-brand' : 'btn-soft' }} btn-sm mt-auto" data-offer="{{ $o->id }}">{{ __('Choisir cette formule') }}</button></div>
      @endforeach</div>
      <div class="panel mb-4"><div class="panel-head"><h2 class="h5 mb-0">{{ __('Disponibilités') }}</h2><span class="small muted"><span class="cu-dot free"></span> {{ __('libre') }} <span class="cu-dot busy ms-2"></span> {{ __('réservé') }}</span></div>
        <div class="cu-cal"><div class="cu-cal-h">@foreach ([__('Lun'), __('Mar'), __('Mer'), __('Jeu'), __('Ven'), __('Sam'), __('Dim')] as $d)<span>{{ $d }}</span>@endforeach</div>
          <div class="cu-cal-g">@foreach ($days as $d)
            @if ($d['state'] === 'free')<button type="button" class="cu-day free {{ $d['today'] ? 'today' : '' }}" data-day="{{ $d['date'] }}" title="{{ date_human($d['date']) }}"><b>{{ $d['day'] }}</b>@if ($d['month'])<i>{{ $d['month'] }}</i>@endif</button>
            @else<span class="cu-day {{ $d['state'] }}" title="{{ $d['state'] === 'busy' ? __('Déjà réservé') : __('Indisponible') }}"><b>{{ $d['day'] }}</b>@if ($d['month'])<i>{{ $d['month'] }}</i>@endif</span>@endif
          @endforeach</div></div>
        <p class="small muted mb-0 mt-2">{{ __('Cliquez sur une date libre pour démarrer une demande.') }}</p></div>
    @else
      <div class="section-head mt-2"><h2 class="h4 mb-0">{{ __('Catalogue') }}</h2><span class="small muted">{{ __('Prix atelier, hors livraison') }}</span></div>
      <div class="cu-products mb-4">@foreach ($t->offers as $o)
        <div class="cu-product"><img src="{{ $o->image ? \Illuminate\Support\Facades\Storage::disk('public')->url($o->image) : media_url(null, $t->visual ?? 'tissage', 'p'.$o->id) }}" alt="" loading="lazy">
          <div class="p-3 d-flex flex-column gap-1 flex-grow-1"><div class="d-flex justify-content-between gap-2"><b>{{ $o->tr('name') }}</b>@if ($o->is_custom)<span class="pill info">{{ __('Sur mesure') }}</span>@endif</div>
            @if ($o->tr('details'))<span class="small muted">{{ $o->tr('details') }}</span>@endif<span class="small muted"><i class="bi bi-hourglass-split"></i> {{ $o->lead_time }}</span>
            <div class="d-flex justify-content-between align-items-center mt-auto pt-2"><span class="price tnum">{{ money($o->price) }}</span><button type="button" class="btn btn-soft btn-sm" data-offer="{{ $o->id }}"><i class="bi bi-plus-lg"></i> {{ __('Commander') }}</button></div></div></div>
      @endforeach</div>
      @if ($t->visit_price !== null)<div class="panel mb-4 cu-visit"><i class="bi bi-door-open"></i><div class="flex-grow-1"><h2 class="h5 mb-1">{{ __('Visite de l\'atelier') }}</h2><p class="mb-0 small">{{ $t->visit_details }} · {{ $t->visit_duration }} · <b>{{ money($t->visit_price) }}</b> {{ __('par personne') }}</p></div></div>@endif
      @if ($t->tr('pro_offer') || $t->restaurants->isNotEmpty() || $t->dishes->isNotEmpty())
        <div class="panel mb-4"><h2 class="h5">{{ __('Pour les restaurateurs') }}</h2>@if ($t->tr('pro_offer'))<p class="small">{{ $t->tr('pro_offer') }}</p>@endif
          @if ($t->restaurants->isNotEmpty())<div class="small muted mb-2">{{ __('Ils travaillent avec cet atelier') }}</div><div class="chip-row mb-2">@foreach ($t->restaurants as $r)<a class="chip" href="{{ route('restaurants.show', $r) }}"><i class="bi bi-shop"></i> {{ $r->name }}</a>@endforeach</div>@endif
          @if ($t->dishes->isNotEmpty())<div class="small muted mb-2 mt-2">{{ __('Objet indissociable de') }}</div><div class="chip-row">@foreach ($t->dishes as $d)<a class="chip" href="{{ route('dishes.show', $d) }}"><i class="bi bi-book"></i> {{ $d->name }}</a>@endforeach</div>@endif</div>
      @endif
    @endif

    {{-- Formulaire de demande --}}
    <div class="panel mb-4" id="booking-form-wrap">
      <h2 class="h4">{{ $craft ? __('Commander ou réserver une visite') : __('Demander une réservation') }}</h2>
      @auth
      <form method="post" action="{{ route('bookings.store', $t) }}" id="booking-form" class="row g-3" data-busy='@json($craft ? [] : $t->busyDates()->values())' data-busy-msg="{{ __('Date indisponible') }}">@csrf
        @if ($craft)
          <div class="col-12"><div class="d-flex gap-3 flex-wrap"><label class="form-check"><input class="form-check-input" type="radio" name="kind" value="commande" checked onchange="document.querySelectorAll('[data-k]').forEach(e=>e.hidden=e.dataset.k!==this.value)"> {{ __('Commander une création') }}</label>@if ($t->visit_price !== null)<label class="form-check"><input class="form-check-input" type="radio" name="kind" value="visite" onchange="document.querySelectorAll('[data-k]').forEach(e=>e.hidden=e.dataset.k!==this.value)"> {{ __('Réserver une visite') }}</label>@endif</div></div>
          <div class="col-md-8" data-k="commande"><label class="form-label" for="bk-o">{{ __('Création') }}</label><select class="form-select" id="bk-o" name="talent_offer_id">@foreach ($t->offers as $o)<option value="{{ $o->id }}" @selected(old('talent_offer_id') == $o->id)>{{ $o->tr('name') }} · {{ money($o->price) }}</option>@endforeach</select></div>
          <div class="col-md-4" data-k="commande"><label class="form-label" for="bk-q">{{ __('Quantité') }}</label><input type="number" class="form-control" id="bk-q" name="quantity" min="1" value="{{ old('quantity', 1) }}"></div>
          <div class="col-md-6" data-k="commande"><label class="form-label" for="bk-m">{{ __('Remise') }}</label><select class="form-select" id="bk-m" name="delivery_mode"><option value="livraison">{{ __('Livraison (frais sur devis)') }}</option><option value="retrait">{{ __('Retrait à l\'atelier') }}</option></select></div>
          <div class="col-md-6" data-k="visite" hidden><label class="form-label" for="bk-d">{{ __('Date de la visite') }}</label><input type="date" class="form-control" id="bk-d" name="event_date" min="{{ now()->addDay()->toDateString() }}"></div>
          <div class="col-md-6" data-k="visite" hidden><label class="form-label" for="bk-g">{{ __('Nombre de personnes') }}</label><input type="number" class="form-control" id="bk-g" name="guests" min="1" value="2"></div>
          <div class="col-md-6"><label class="form-label" for="bk-c">{{ __('Ville') }}</label><input class="form-control" id="bk-c" name="city" value="{{ old('city', auth()->user()->city) }}"></div>
        @else
          <input type="hidden" name="kind" value="prestation">
          <div class="col-12"><label class="form-label" for="bk-o">{{ __('Formule') }}</label><select class="form-select" id="bk-o" name="talent_offer_id">@foreach ($t->offers as $i => $o)<option value="{{ $o->id }}" @selected(old('talent_offer_id', $t->offers->get(1)?->id ?? $o->id) == $o->id)>{{ $o->tr('name') }} · {{ $o->duration }} · {{ money($o->price) }}</option>@endforeach</select></div>
          <div class="col-md-6"><label class="form-label" for="bk-d">{{ __('Date de l\'événement') }}</label><input type="date" class="form-control" id="bk-d" name="event_date" required min="{{ $t->earliestDate() }}" value="{{ old('event_date') }}"><div class="small text-danger mt-1" data-date-msg hidden>{{ __('Cette date est déjà réservée. Choisissez-en une autre.') }}</div></div>
          <div class="col-md-6"><label class="form-label" for="bk-oc">{{ __('Occasion') }}</label><select class="form-select" id="bk-oc" name="occasion">@foreach (config('troofe.occasions') as $oc)<option value="{{ $oc }}" @selected(old('occasion') === $oc)>{{ __($oc) }}</option>@endforeach</select></div>
          <div class="col-md-6"><label class="form-label" for="bk-c">{{ __('Ville') }}</label><input class="form-control" id="bk-c" name="city" required value="{{ old('city', auth()->user()->city ?? $t->city) }}"></div>
          <div class="col-md-6"><label class="form-label" for="bk-v">{{ __('Lieu (salle, adresse)') }}</label><input class="form-control" id="bk-v" name="venue" value="{{ old('venue') }}"></div>
          <div class="col-md-6"><label class="form-label" for="bk-g">{{ __('Nombre d\'invités') }}</label><input type="number" class="form-control" id="bk-g" name="guests" min="1" value="{{ old('guests', 100) }}"></div>
        @endif
        <div class="col-md-6"><label class="form-label" for="bk-p">{{ __('Téléphone de contact') }}</label><input class="form-control" id="bk-p" name="contact_phone" required value="{{ old('contact_phone', auth()->user()->phone) }}"></div>
        <div class="col-12"><label class="form-label" for="bk-msg">{{ __('Votre message') }}</label><textarea class="form-control" id="bk-msg" name="message" rows="3" placeholder="{{ $craft ? __('Personnalisation, couleurs, dimensions…') : __('Déroulé, horaires, contraintes techniques (sono, scène, loge)…') }}">{{ old('message') }}</textarea></div>
        <div class="col-12 d-flex justify-content-between align-items-center flex-wrap gap-2"><span class="small muted"><i class="bi bi-shield-check"></i> {{ __('Rien n\'est dû avant votre accord sur le devis.') }}</span><button class="btn btn-brand"><i class="bi bi-send"></i> {{ __('Envoyer la demande') }}</button></div>
      </form>
      @else
        <p class="mb-2">{{ __('Connectez-vous pour envoyer une demande et la suivre dans votre espace.') }}</p><a class="btn btn-brand" href="{{ route('login') }}">{{ __('Se connecter') }}</a> <a class="btn btn-ghost" href="{{ route('register') }}">{{ __('Créer un compte') }}</a>
      @endauth
    </div>

    <div class="section-head mt-2"><h2 class="h4 mb-0">{{ __('Galerie') }}</h2></div>
    <div class="gallery mb-4">@foreach ($gallery as $i => $g)<a class="g-item {{ $i === 0 ? 'big' : '' }}" href="{{ $g }}" data-lightbox><img src="{{ $g }}" alt="{{ $t->name }}" loading="lazy"></a>@endforeach</div>
    @if ($events->isNotEmpty())<div class="section-head"><h2 class="h4 mb-0">{{ __('À l\'affiche') }}</h2></div><div class="grid g3 mb-4">@foreach ($events as $e)<x-event-card :event="$e" />@endforeach</div>@endif
    <x-reviews :model="$t" type="talent" :reviews="$reviews" :owner-id="$t->owner_id" />
  </div>

  <aside class="sticky-side d-grid gap-3">
    <div class="panel cu-book"><div class="small muted">{{ $craft ? __('Créations à partir de') : __('Prestation à partir de') }}</div><div class="price tnum fs-3">{{ $p ? money($p) : __('Sur devis') }}</div>
      <a href="#booking-form-wrap" class="btn btn-brand w-100 mt-2">{{ $craft ? __('Commander') : __('Demander une réservation') }}</a>
      <ul class="cu-ticks mt-3 mb-0"><li><i class="bi bi-check2"></i> {{ $craft ? __('Commande transmise directement à l\'atelier') : __('Devis envoyé par l\'artiste sous 48 h') }}</li><li><i class="bi bi-check2"></i> {{ __('Rien à payer avant votre accord') }}</li><li><i class="bi bi-check2"></i> {{ __('Suivi et notifications par e-mail') }}</li></ul></div>
    <div class="panel"><h3 class="h6">{{ $craft ? __('L\'atelier') : __('En bref') }}</h3>
      <dl class="kv mb-3">@foreach ($facts as [$a, $b])<dt>{{ $a }}</dt><dd>{{ $b }}</dd>@endforeach @if ($t->phone)<dt>{{ __('Téléphone') }}</dt><dd class="tnum"><a href="tel:{{ preg_replace('/\s/', '', $t->phone) }}">{{ $t->phone }}</a></dd>@endif</dl>
      @if ($t->whatsappUrl())<a class="btn btn-ghost btn-sm w-100" href="{{ $t->whatsappUrl() }}" target="_blank" rel="noopener"><i class="bi bi-whatsapp"></i> {{ __('Écrire sur WhatsApp') }}</a>@endif
      <div class="small mt-3 {{ $t->is_verified ? '' : 'muted' }}"><i class="bi {{ $t->is_verified ? 'bi-patch-check-fill cu-verif' : 'bi-info-circle' }}"></i> {{ $t->is_verified ? __('Identité et références vérifiées par l\'équipe Trôofê.') : __('Profil non encore vérifié.') }}</div></div>
    <x-ad-slot code="culture_side" />
  </aside></div>
  @if ($similar->isNotEmpty())<div class="mt-5"><div class="sh"><h2>{{ __('Vous aimerez aussi') }}</h2></div><div class="grid">@foreach ($similar as $x)<x-talent-card :talent="$x" />@endforeach</div></div>@endif
</div></section>
<div class="modal fade" id="lightbox" tabindex="-1" aria-label="{{ __('Galerie') }}"><div class="modal-dialog modal-xl modal-dialog-centered"><div class="modal-content bg-transparent border-0"><button type="button" class="btn-close btn-close-white ms-auto mb-2" data-bs-dismiss="modal" aria-label="{{ __('Fermer') }}"></button><img src="" alt="" class="w-100 rounded"></div></div></div>
@endsection

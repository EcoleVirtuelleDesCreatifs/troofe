@extends('layouts.app', ['title' => $restaurant->name, 'description' => $restaurant->tr('tagline'), 'ogImage' => $restaurant->coverUrl()])
@section('content')
@php $open = $restaurant->isOpenNow(); $avg = $restaurant->ratingAverage(); $nbAvis = $restaurant->ratingCount(); @endphp
<section class="section"><div class="wrap gutter">
  @include('partials.crumbs', ['items' => [__('Restaurants') => route('restaurants.index'), $restaurant->name => null]])
  @unless ($restaurant->isPublished())<div class="notice warn mb-3"><i class="bi bi-eye-slash"></i><div>{{ __('Aperçu : cette fiche n\'est pas encore visible du public.') }}</div></div>@endunless
  <div class="detail-hero mb-4"><img src="{{ $restaurant->coverUrl() }}" alt="{{ $restaurant->name }}"></div>
  <div class="two-col"><div>
    <div class="d-flex justify-content-between gap-3 flex-wrap align-items-start"><div>
      <div class="eyebrow"><a href="{{ route('restaurants.index', ['cuisine' => $restaurant->cuisine]) }}">{{ __($restaurant->cuisine) }}</a> · <a href="{{ route('restaurants.index', ['city' => $restaurant->city]) }}">{{ $restaurant->city }}</a></div>
      <h1 class="mt-1 mb-2">{{ $restaurant->name }}@if ($restaurant->is_verified) <i class="bi bi-patch-check-fill cu-verif" title="{{ __('Vérifié par Trôofê') }}"></i>@endif</h1>
      <div class="d-flex gap-3 flex-wrap align-items-center small">
        @if ($nbAvis)<span><x-stars :value="$avg" /> <b class="tnum">{{ number_format($avg, 1, ',', '') }}</b> <span class="muted">({{ trans_choice(':count avis|:count avis', $nbAvis) }})</span></span>@else<span class="muted">{{ __('Pas encore d\'avis') }}</span>@endif
        <span class="tnum">{{ $restaurant->priceLabel() }}</span>
        <span class="pill {{ $open ? 'ok' : '' }}">{{ $open ? __('Ouvert maintenant') : __('Fermé actuellement') }}</span>
        @if ($restaurant->is_featured)<span class="tag spon">{{ __('Sélection') }}</span>@endif
      </div></div>
      <div class="d-flex gap-2"><x-fav-button type="restaurant" :model="$restaurant" look="btn btn-ghost"> {{ __('Enregistrer') }}</x-fav-button><button class="btn btn-ghost btn-icon" data-share aria-label="{{ __('Partager') }}"><i class="bi bi-share"></i></button></div>
    </div>
    @if ($restaurant->tr('tagline'))<p class="lead mt-3 mb-2">{{ $restaurant->tr('tagline') }}</p>@endif
    @if ($restaurant->tr('description'))<p class="prose mt-3">{!! nl2br(e($restaurant->tr('description'))) !!}</p>@endif
    <div class="d-flex flex-wrap gap-2 mb-4">
      @if ($restaurant->accepts_orders)<span class="pill nodot"><i class="bi bi-bag-check"></i> {{ __('Commande en ligne') }}</span>@endif
      @if ($restaurant->delivery)<span class="pill nodot"><i class="bi bi-bicycle"></i> {{ __('Livraison') }}</span><span class="pill nodot"><i class="bi bi-box-seam"></i> {{ __('Retrait') }}</span>@endif
    </div>
    @if ($restaurant->dishes->isNotEmpty())<div class="chip-row mb-4">@foreach ($restaurant->dishes as $d)<a class="chip" href="{{ route('dishes.show', $d) }}"><i class="bi bi-book"></i> {{ $d->name }}</a>@endforeach</div>@endif

    <div class="panel mb-4" id="carte">
      <div class="panel-head"><h2 class="h4 mb-0">{{ __('Menu') }}</h2>@if ($restaurant->accepts_orders)<span class="small muted">{{ __('Panier limité à un restaurant par commande') }}</span>@else<span class="small muted">{{ __('Commande en ligne non disponible') }}</span>@endif</div>
      @if ($menu->isEmpty())<p class="muted mb-0">{{ __('La carte sera bientôt en ligne.') }}</p>@endif
      <form method="post" action="{{ route('orders.food', $restaurant) }}" data-order-form data-fee="{{ $restaurant->delivery_fee }}">@csrf
        @if ($menu->count() > 1)<div class="segmented mb-2" id="menu-tabs"><button type="button" class="on" data-cat="">{{ __('Tout') }}</button>@foreach ($menu->keys() as $c)<button type="button" data-cat="{{ $c }}">{{ __($c) }}</button>@endforeach</div>@endif
        <div id="menu-list">
        @foreach ($menu as $cat => $items)
          @foreach ($items as $m)
            <div class="menu-item" data-cat="{{ $cat }}">
              @if ($m->image)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($m->image) }}" alt="" loading="lazy">@endif
              <div class="mi-body"><b>{{ $m->tr('name') }}</b>@if ($m->tr('description'))<div class="small muted">{{ $m->tr('description') }}</div>@endif<div class="tnum fw-semibold mt-1">{{ money($m->price) }}</div></div>
              @if ($restaurant->accepts_orders)<div class="qty"><button type="button" class="btn btn-ghost btn-icon btn-sm" data-step="-1" aria-label="{{ __('Retirer') }}"><i class="bi bi-dash"></i></button><input type="number" min="0" max="50" value="0" name="items[{{ $m->id }}]" class="form-control form-control-sm" data-price="{{ $m->price }}" aria-label="{{ __('Quantité') }} {{ $m->name }}"><button type="button" class="btn btn-ghost btn-icon btn-sm" data-step="1" aria-label="{{ __('Ajouter') }}"><i class="bi bi-plus"></i></button></div>@endif
            </div>
          @endforeach
        @endforeach
        </div>
        @if ($restaurant->accepts_orders && $menu->isNotEmpty())
          <div class="row g-2 mt-3">
            <div class="col-12"><div class="d-flex gap-3"><label class="form-check"><input type="radio" class="form-check-input" name="fulfilment" value="retrait" checked> {{ __('À emporter') }}</label>@if ($restaurant->delivery)<label class="form-check"><input type="radio" class="form-check-input" name="fulfilment" value="livraison"> {{ __('Livraison') }} (+{{ money($restaurant->delivery_fee) }})</label>@endif</div></div>
            <div class="col-12" data-address hidden><input name="delivery_address" class="form-control" placeholder="{{ __('Adresse de livraison, repère') }}" value="{{ old('delivery_address') }}"></div>
            <div class="col-md-6"><input name="contact_phone" class="form-control" required placeholder="{{ __('Téléphone') }}" value="{{ old('contact_phone', auth()->user()?->phone) }}"></div>
            <div class="col-md-6"><input name="note" class="form-control" placeholder="{{ __('Note pour la cuisine (facultatif)') }}" maxlength="500"></div>
          </div>
          <div class="order-bar d-flex justify-content-between align-items-center gap-2 mt-3 p-3 rounded">
            <span><span class="small muted">{{ __('Articles') }} : <b data-order-count>0</b></span> · <b class="tnum fs-5" data-order-total>0</b></span>
            @auth<button class="btn btn-brand" data-order-submit disabled><i class="bi bi-lock"></i> {{ __('Payer et commander') }}</button>@else<a class="btn btn-brand" href="{{ route('login') }}">{{ __('Se connecter pour commander') }}</a>@endauth
          </div>
          <p class="small muted mt-2 mb-0">{{ __('Paiement sécurisé : Orange Money, MTN MoMo, Moov, Wave ou carte. Vous recevez la confirmation par e-mail.') }}</p>
        @endif
      </form>
    </div>

    @if ($events->isNotEmpty())<div class="section-head"><h2 class="h4 mb-0">{{ __('Événements ici') }}</h2></div><div class="grid g3 mb-4">@foreach ($events as $e)<x-event-card :event="$e" />@endforeach</div>@endif
    @if ($restaurant->craftsmen->isNotEmpty())<div class="section-head"><h2 class="h4 mb-0">{{ __('Artisans de la maison') }}</h2><span class="small muted">{{ __('Vaisselle, linge et décor fournis par') }}</span></div><div class="grid g3 mb-4">@foreach ($restaurant->craftsmen as $t)<x-talent-card :talent="$t" />@endforeach</div>@endif
    <x-reviews :model="$restaurant" type="restaurant" :reviews="$reviews" :owner-id="$restaurant->owner_id" />
    {{ $reviews->fragment('avis')->links() }}
  </div>
  <aside class="sticky-side d-grid gap-3">
    <div class="panel"><h3 class="h6">{{ __('Coordonnées') }}</h3>
      <dl class="kv mb-3">
        <dt>{{ __('Adresse') }}</dt><dd>{{ collect([$restaurant->address, $restaurant->district, $restaurant->city])->filter()->implode(', ') }}</dd>
        @if ($restaurant->phone)<dt>{{ __('Téléphone') }}</dt><dd class="tnum user-select-all"><a href="tel:{{ preg_replace('/\s/', '', $restaurant->phone) }}">{{ $restaurant->phone }}</a></dd>@endif
        @if ($restaurant->whatsapp)<dt>WhatsApp</dt><dd class="tnum user-select-all">{{ $restaurant->whatsapp }}</dd>@endif
      </dl>
      <div class="map-ph"><i class="bi bi-geo-alt-fill pin"></i></div>
      <a class="small d-inline-block mt-2" target="_blank" rel="noopener" href="https://www.openstreetmap.org/search?query={{ urlencode(collect([$restaurant->address, $restaurant->city])->filter()->implode(' ')) }}">{{ __('Ouvrir dans OpenStreetMap') }} <i class="bi bi-box-arrow-up-right"></i></a>
      <div class="d-grid gap-2 mt-2">
        @if ($restaurant->whatsappUrl())<a class="btn btn-ghost btn-sm w-100" href="{{ $restaurant->whatsappUrl() }}" target="_blank" rel="noopener"><i class="bi bi-whatsapp"></i> {{ __('Écrire sur WhatsApp') }}</a>@endif
        @if ($restaurant->lat)<a class="btn btn-ghost btn-sm w-100" target="_blank" rel="noopener" href="https://www.google.com/maps/search/?api=1&query={{ $restaurant->lat }},{{ $restaurant->lng }}"><i class="bi bi-map"></i> {{ __('Itinéraire') }}</a>@endif
      </div>
    </div>
    @if ($restaurant->opening_hours)
    <div class="panel"><h3 class="h6">{{ __('Horaires') }}</h3>
      <dl class="kv tnum mb-2">@foreach ($restaurant->opening_hours as $day => $h)<dt class="text-capitalize">{{ __(mb_strtolower(mb_substr($day, 0, 3))) }}.</dt><dd>{{ $h }}</dd>@endforeach</dl>
      <div class="small muted">{{ __('Mis à jour par le restaurant.') }}</div>
    </div>
    @endif
    <x-ad-slot code="resto_side" />
  </aside></div>
  @if ($similar->isNotEmpty())<div class="mt-5"><div class="sh"><h2>{{ __('Vous aimerez aussi') }}</h2></div><div class="grid">@foreach ($similar as $r)<x-restaurant-card :r="$r" />@endforeach</div></div>@endif
</div></section>
@endsection

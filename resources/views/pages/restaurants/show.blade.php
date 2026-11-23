@extends('layouts.app', ['title' => $restaurant->name, 'description' => $restaurant->tr('tagline'), 'ogImage' => $restaurant->coverUrl()])
@section('content')
<x-hero-band :img="$restaurant->coverUrl()" :eyebrow="__($restaurant->cuisine).' · '.$restaurant->city"
  :title="$restaurant->name" :verified="$restaurant->is_verified" :lead="$restaurant->tr('tagline')"
  :stats="[[$restaurant->ratingCount() ? number_format($restaurant->ratingAverage(), 1, ',', '') : '—', trans_choice(':count avis|:count avis', $restaurant->ratingCount())], [$restaurant->priceLabel(), __('gamme de prix')], [$restaurant->menuItems->count(), __('plats à la carte')]]" />
<section class="section"><div class="wrap gutter">
  @include('partials.crumbs', ['items' => [__('Restaurants') => route('restaurants.index'), $restaurant->name => null]])
  @unless ($restaurant->isPublished())<div class="notice warn mb-3"><i class="bi bi-eye-slash"></i><div>{{ __('Aperçu : cette fiche n\'est pas encore visible du public.') }}</div></div>@endunless
  <div class="two-col"><div>
    <div class="d-flex gap-2 flex-wrap mb-4">
      @if ($restaurant->accepts_orders && $restaurant->menuItems->isNotEmpty())<a href="#carte" class="btn btn-brand"><i class="bi bi-bag"></i> {{ __('Commander') }}</a>@endif
      <x-fav-button type="restaurant" :model="$restaurant" look="btn btn-ghost"> {{ __('Favori') }}</x-fav-button>
      <x-follow-button type="restaurant" :model="$restaurant" />
    </div>
    @if ($restaurant->tr('description'))<h2 class="h4">{{ __('Présentation') }}</h2><p class="prose">{!! nl2br(e($restaurant->tr('description'))) !!}</p>@endif
    @if ($restaurant->dishes->isNotEmpty())<div class="chip-row mb-4">@foreach ($restaurant->dishes as $d)<a class="chip" href="{{ route('dishes.show', $d) }}"><i class="bi bi-book"></i> {{ $d->name }}</a>@endforeach</div>@endif

    <div class="panel mb-4" id="carte">
      <div class="panel-head"><h2 class="h4 mb-0">{{ __('La carte') }}</h2>@if ($restaurant->accepts_orders)<span class="small muted">{{ $restaurant->delivery ? __('À emporter ou en livraison') : __('À emporter') }}</span>@endif</div>
      @if ($menu->isEmpty())<p class="muted mb-0">{{ __('La carte sera bientôt en ligne.') }}</p>@endif
      <form method="post" action="{{ route('orders.food', $restaurant) }}" data-order-form data-fee="{{ $restaurant->delivery_fee }}">@csrf
        @foreach ($menu as $cat => $items)
          <h3 class="h6 mt-3 text-uppercase muted">{{ __($cat) }}</h3>
          @foreach ($items as $m)
            <div class="menu-item">
              @if ($m->image)<img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($m->image) }}" alt="" loading="lazy">@endif
              <div class="mi-body"><b>{{ $m->tr('name') }}</b>@if ($m->tr('description'))<div class="small muted">{{ $m->tr('description') }}</div>@endif<div class="tnum fw-semibold mt-1">{{ money($m->price) }}</div></div>
              @if ($restaurant->accepts_orders)<div class="qty"><button type="button" class="btn btn-ghost btn-icon btn-sm" data-step="-1" aria-label="{{ __('Retirer') }}"><i class="bi bi-dash"></i></button><input type="number" min="0" max="50" value="0" name="items[{{ $m->id }}]" class="form-control form-control-sm" data-price="{{ $m->price }}" aria-label="{{ __('Quantité') }} {{ $m->name }}"><button type="button" class="btn btn-ghost btn-icon btn-sm" data-step="1" aria-label="{{ __('Ajouter') }}"><i class="bi bi-plus"></i></button></div>@endif
            </div>
          @endforeach
        @endforeach
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
    <div class="panel"><h3 class="h6">{{ __('Infos pratiques') }}</h3>
      <dl class="kv mb-3">
        <dt>{{ __('Adresse') }}</dt><dd>{{ collect([$restaurant->address, $restaurant->district, $restaurant->city])->filter()->implode(', ') }}</dd>
        @if ($restaurant->phone)<dt>{{ __('Téléphone') }}</dt><dd class="tnum"><a href="tel:{{ preg_replace('/\s/', '', $restaurant->phone) }}">{{ $restaurant->phone }}</a></dd>@endif
        @foreach ($restaurant->opening_hours ?? [] as $day => $h)<dt>{{ __($day) }}</dt><dd>{{ $h }}</dd>@endforeach
      </dl>
      @if ($restaurant->whatsappUrl())<a class="btn btn-ghost btn-sm w-100" href="{{ $restaurant->whatsappUrl() }}" target="_blank" rel="noopener"><i class="bi bi-whatsapp"></i> {{ __('Écrire sur WhatsApp') }}</a>@endif
      @if ($restaurant->lat)<a class="btn btn-ghost btn-sm w-100 mt-2" target="_blank" rel="noopener" href="https://www.google.com/maps/search/?api=1&query={{ $restaurant->lat }},{{ $restaurant->lng }}"><i class="bi bi-map"></i> {{ __('Itinéraire') }}</a>@endif
    </div>
    <x-ad-slot code="resto_side" />
  </aside></div>
  @if ($similar->isNotEmpty())<div class="mt-5"><div class="sh"><h2>{{ __('Vous aimerez aussi') }}</h2></div><div class="grid">@foreach ($similar as $r)<x-restaurant-card :r="$r" />@endforeach</div></div>@endif
</div></section>
@endsection

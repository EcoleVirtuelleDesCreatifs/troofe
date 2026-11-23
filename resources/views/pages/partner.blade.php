@extends('layouts.app', ['title' => __('Devenir partenaire')])
@section('content')
<x-hero-band short :img="media_url(null, 'tissage', 'partner')" :eyebrow="__('Professionnels')" :title="__('Rejoignez Trôofê')" :lead="__('Créez votre fiche, recevez commandes et réservations, encaissez en Mobile Money. Gratuit pour commencer.')" />
<section class="section"><div class="wrap gutter">
  <div class="row g-4">
    @foreach ([['restaurateur', 'bi-shop', __('Restaurant ou maquis'), __('Fiche, carte en ligne, commandes à emporter et en livraison, avis clients.')], ['artiste', 'bi-music-note-beamed', __('Troupe, artiste ou artisan'), __('Formules ou catalogue, calendrier de disponibilités, devis et commandes.')], ['organisateur', 'bi-calendar-event', __('Organisateur d\'événements'), __('Billetterie avec QR code, contrôle d\'accès, rappel automatique aux participants.')], ['annonceur', 'bi-megaphone', __('Annonceur'), __('Campagnes sur les emplacements du site, statistiques d\'affichage et de clics.')]] as [$role, $icon, $t, $d])
      <div class="col-md-6 col-lg-3"><div class="panel h-100 d-flex flex-column"><i class="bi {{ $icon }} fs-3" style="color:var(--ocre)"></i><h2 class="h5 mt-2">{{ $t }}</h2><p class="small muted">{{ $d }}</p>
        <div class="mt-auto">@auth
          @if (auth()->user()->hasRole($role))<a href="{{ url('/pro') }}" class="btn btn-soft w-100">{{ __('Ouvrir mon espace pro') }}</a>
          @else<form method="post" action="{{ route('partner.store') }}">@csrf<input type="hidden" name="role" value="{{ $role }}"><button class="btn btn-brand w-100">{{ __('Commencer') }}</button></form>@endif
        @else<a href="{{ route('register', ['pro' => $role]) }}" class="btn btn-brand w-100">{{ __('Créer mon compte pro') }}</a>@endauth</div></div></div>
    @endforeach
  </div>
  <div class="panel mt-4"><h2 class="h5">{{ __('Comment ça marche') }}</h2><ol class="mb-0"><li>{{ __('Créez votre compte et confirmez votre e-mail.') }}</li><li>{{ __('Ouvrez votre espace pro et créez votre fiche (photos, textes en français et en anglais).') }}</li><li>{{ __('L\'équipe valide la fiche sous 48 h : vous êtes prévenu par e-mail.') }}</li><li>{{ __('Recevez commandes et demandes par e-mail et dans votre espace ; répondez en un clic.') }}</li></ol></div>
  <p class="text-center mt-4"><a href="{{ route('pricing') }}" class="link-arrow">{{ __('Comparer les formules') }} <i class="bi bi-arrow-right"></i></a></p>
</div></section>
@endsection

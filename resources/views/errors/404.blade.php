@extends('layouts.app', ['title' => '404'])
@section('content')
<section class="section"><div class="wrap gutter text-center" style="max-width:560px">
  <div class="display" style="font-size:5rem;color:var(--ocre)">404</div>
  <h1 class="h3">{{ __($exception?->getMessage() ?: [403 => 'Accès refusé', 404 => 'Page introuvable', 419 => 'Session expirée, rechargez la page', 500 => 'Une erreur est survenue', 503 => 'Maintenance en cours'][404]) }}</h1>
  <a href="{{ route('home') }}" class="btn btn-brand mt-3">{{ __('Retour à l\'accueil') }}</a>
</div></section>
@endsection

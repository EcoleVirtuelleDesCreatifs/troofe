@extends('layouts.app', ['title' => __('Mot de passe oublié')])
@section('content')
<x-auth-card>
  <h1 class="h4">{{ __('Mot de passe oublié') }}</h1><p class="muted small">{{ __('Indiquez votre adresse : nous vous envoyons un lien pour choisir un nouveau mot de passe.') }}</p>
  <form method="post" action="{{ route('password.email') }}" class="d-grid gap-3">@csrf
    <input type="email" class="form-control" name="email" value="{{ old('email') }}" required placeholder="{{ __('Adresse e-mail') }}" aria-label="{{ __('Adresse e-mail') }}">
    <button class="btn btn-brand">{{ __('Envoyer le lien') }}</button>
  </form>
  <p class="small text-center mt-3 mb-0"><a href="{{ route('login') }}">{{ __('Retour à la connexion') }}</a></p>
</x-auth-card>
@endsection

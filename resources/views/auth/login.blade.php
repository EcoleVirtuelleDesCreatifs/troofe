@extends('layouts.app', ['title' => __('Connexion')])
@section('content')
<x-auth-card>
  <h1 class="h4 mb-3">{{ __('Connexion') }}</h1>
  <form method="post" action="{{ route('login') }}" class="d-grid gap-3">@csrf
    <div><label class="form-label" for="email">{{ __('Adresse e-mail') }}</label><input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required autofocus autocomplete="email"></div>
    <div><div class="d-flex justify-content-between"><label class="form-label" for="password">{{ __('Mot de passe') }}</label><a class="small" href="{{ route('password.request') }}">{{ __('Mot de passe oublié ?') }}</a></div><input type="password" class="form-control" id="password" name="password" required autocomplete="current-password"></div>
    <label class="form-check"><input type="checkbox" class="form-check-input" name="remember" value="1"> {{ __('Rester connecté') }}</label>
    <button class="btn btn-brand">{{ __('Se connecter') }}</button>
  </form>
  <p class="small text-center mt-3 mb-0">{{ __('Pas encore de compte ?') }} <a href="{{ route('register') }}">{{ __('Créer un compte') }}</a></p>
</x-auth-card>
@endsection

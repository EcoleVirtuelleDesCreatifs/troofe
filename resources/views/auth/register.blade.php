@extends('layouts.app', ['title' => __('Créer un compte')])
@section('content')
<x-auth-card>
  <h1 class="h4 mb-3">{{ __('Créer un compte') }}</h1>
  <form method="post" action="{{ route('register') }}" class="row g-3">@csrf
    <div class="col-12"><label class="form-label" for="name">{{ __('Nom complet') }}</label><input class="form-control" id="name" name="name" value="{{ old('name') }}" required autocomplete="name"></div>
    <div class="col-12"><label class="form-label" for="email">{{ __('Adresse e-mail') }}</label><input type="email" class="form-control" id="email" name="email" value="{{ old('email') }}" required autocomplete="email"></div>
    <div class="col-md-6"><label class="form-label" for="phone">{{ __('Téléphone') }}</label><input class="form-control" id="phone" name="phone" value="{{ old('phone') }}" autocomplete="tel"></div>
    <div class="col-md-6"><label class="form-label" for="city">{{ __('Ville') }}</label><input class="form-control" id="city" name="city" value="{{ old('city') }}"></div>
    <div class="col-md-6"><label class="form-label" for="password">{{ __('Mot de passe') }}</label><input type="password" class="form-control" id="password" name="password" required autocomplete="new-password"><div class="form-text">{{ __('8 caractères minimum, lettres et chiffres.') }}</div></div>
    <div class="col-md-6"><label class="form-label" for="password_confirmation">{{ __('Confirmation') }}</label><input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required autocomplete="new-password"></div>
    <div class="col-12"><label class="form-label" for="pro_role">{{ __('Vous êtes aussi…') }}</label><select class="form-select" id="pro_role" name="pro_role"><option value="">{{ __('Un particulier') }}</option>@foreach (\App\Models\User::PRO_ROLES as $r)<option value="{{ $r }}" @selected(old('pro_role', request('pro')) === $r)>{{ __(\App\Models\User::ROLES[$r]) }}</option>@endforeach</select></div>
    <div class="col-12"><label class="form-check"><input type="checkbox" class="form-check-input" name="newsletter" value="1" @checked(old('newsletter', true))> {{ __('Recevoir la lettre Trôofê (désinscription en un clic)') }}</label>
      <label class="form-check"><input type="checkbox" class="form-check-input" name="terms" value="1" required> {!! __('J\'accepte les <a href=":url" target="_blank">conditions d\'utilisation</a>', ['url' => route('page', 'conditions')]) !!}</label></div>
    <div class="col-12"><button class="btn btn-brand w-100">{{ __('Créer mon compte') }}</button></div>
  </form>
  <p class="small text-center mt-3 mb-0">{{ __('Déjà inscrit ?') }} <a href="{{ route('login') }}">{{ __('Se connecter') }}</a></p>
</x-auth-card>
@endsection

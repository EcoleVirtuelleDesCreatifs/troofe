@extends('layouts.app', ['title' => __('Nouveau mot de passe')])
@section('content')
<x-auth-card>
  <h1 class="h4 mb-3">{{ __('Nouveau mot de passe') }}</h1>
  <form method="post" action="{{ route('password.update') }}" class="d-grid gap-3">@csrf
    <input type="hidden" name="token" value="{{ $token }}">
    <div><label class="form-label" for="email">{{ __('Adresse e-mail') }}</label><input type="email" class="form-control" id="email" name="email" value="{{ old('email', $email) }}" required></div>
    <div><label class="form-label" for="password">{{ __('Nouveau mot de passe') }}</label><input type="password" class="form-control" id="password" name="password" required autocomplete="new-password"></div>
    <div><label class="form-label" for="password_confirmation">{{ __('Confirmation') }}</label><input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required></div>
    <button class="btn btn-brand">{{ __('Enregistrer') }}</button>
  </form>
</x-auth-card>
@endsection

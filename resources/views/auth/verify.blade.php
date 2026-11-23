@extends('layouts.app', ['title' => __('Confirmez votre adresse')])
@section('content')
<x-auth-card>
  <div class="text-center"><i class="bi bi-envelope-check" style="font-size:2.5rem;color:var(--ocre)"></i>
  <h1 class="h4 mt-2">{{ __('Confirmez votre adresse e-mail') }}</h1>
  <p class="muted">{{ __('Nous avons envoyé un lien à :email. Cliquez dessus pour activer votre compte (pensez à vérifier les indésirables).', ['email' => auth()->user()->email]) }}</p>
  <form method="post" action="{{ route('verification.send') }}">@csrf<button class="btn btn-brand">{{ __('Renvoyer le lien') }}</button></form>
  <a href="{{ route('account.profile') }}" class="small d-inline-block mt-3">{{ __('Corriger mon adresse') }}</a></div>
</x-auth-card>
@endsection

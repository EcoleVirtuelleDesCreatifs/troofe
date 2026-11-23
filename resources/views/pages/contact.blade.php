@extends('layouts.app', ['title' => __('Contact')])
@section('content')
<section class="section"><div class="wrap gutter" style="max-width:720px">
  <h1 class="h2">{{ __('Nous écrire') }}</h1><p class="muted">{{ __('Une question, un partenariat, un signalement ? Réponse sous 48 h ouvrées.') }}</p>
  <form method="post" action="{{ route('contact.store') }}" class="panel row g-3">@csrf
    <div class="col-md-6"><label class="form-label" for="n">{{ __('Nom') }}</label><input class="form-control" id="n" name="name" required value="{{ old('name', auth()->user()?->name) }}"></div>
    <div class="col-md-6"><label class="form-label" for="e">{{ __('E-mail') }}</label><input type="email" class="form-control" id="e" name="email" required value="{{ old('email', auth()->user()?->email) }}"></div>
    <div class="col-12"><label class="form-label" for="s">{{ __('Objet') }}</label><input class="form-control" id="s" name="subject" required value="{{ old('subject') }}"></div>
    <div class="col-12"><label class="form-label" for="m">{{ __('Message') }}</label><textarea class="form-control" id="m" name="message" rows="6" required minlength="10">{{ old('message') }}</textarea></div>
    <input type="text" name="website" class="d-none" tabindex="-1" autocomplete="off">
    <div class="col-12"><button class="btn btn-brand">{{ __('Envoyer') }}</button></div>
  </form>
</div></section>
@endsection

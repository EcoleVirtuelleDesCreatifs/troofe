@extends('account.layout', ['title' => __('Mon profil')])
@section('account')
<form method="post" action="{{ route('account.profile.update') }}" enctype="multipart/form-data" class="panel mb-4 row g-3">@csrf @method('put')
  <div class="col-12 d-flex gap-3 align-items-center"><x-avatar :user="auth()->user()" :size="64" /><div><label class="form-label" for="avatar">{{ __('Photo de profil') }}</label><input type="file" class="form-control form-control-sm" id="avatar" name="avatar" accept="image/*"></div></div>
  <div class="col-md-6"><label class="form-label" for="name">{{ __('Nom complet') }}</label><input class="form-control" id="name" name="name" required value="{{ old('name', auth()->user()->name) }}"></div>
  <div class="col-md-6"><label class="form-label" for="email">{{ __('Adresse e-mail') }}</label><input type="email" class="form-control" id="email" name="email" required value="{{ old('email', auth()->user()->email) }}"><div class="form-text">{{ __('Un changement d\'adresse demande une nouvelle confirmation.') }}</div></div>
  <div class="col-md-4"><label class="form-label" for="phone">{{ __('Téléphone') }}</label><input class="form-control" id="phone" name="phone" value="{{ old('phone', auth()->user()->phone) }}"></div>
  <div class="col-md-4"><label class="form-label" for="city">{{ __('Ville') }}</label><input class="form-control" id="city" name="city" value="{{ old('city', auth()->user()->city) }}"></div>
  <div class="col-md-4"><label class="form-label" for="country">{{ __('Pays') }}</label><input class="form-control" id="country" name="country" value="{{ old('country', auth()->user()->country) }}"></div>
  <div class="col-md-4"><label class="form-label" for="locale">{{ __('Langue du site et des e-mails') }}</label><select class="form-select" id="locale" name="locale">@foreach (\App\Http\Middleware\SetLocale::LOCALES as $c => $l)<option value="{{ $c }}" @selected(auth()->user()->locale === $c)>{{ $l }}</option>@endforeach</select></div>
  <div class="col-12"><button class="btn btn-brand">{{ __('Enregistrer') }}</button></div>
</form>
<form method="post" action="{{ route('account.password') }}" class="panel mb-4 row g-3">@csrf @method('put')
  <h2 class="h5 col-12 mb-0">{{ __('Mot de passe') }}</h2>
  <div class="col-md-4"><label class="form-label" for="cp">{{ __('Mot de passe actuel') }}</label><input type="password" class="form-control" id="cp" name="current_password" required autocomplete="current-password"></div>
  <div class="col-md-4"><label class="form-label" for="np">{{ __('Nouveau mot de passe') }}</label><input type="password" class="form-control" id="np" name="password" required autocomplete="new-password"></div>
  <div class="col-md-4"><label class="form-label" for="npc">{{ __('Confirmation') }}</label><input type="password" class="form-control" id="npc" name="password_confirmation" required></div>
  <div class="col-12"><button class="btn btn-ghost">{{ __('Changer le mot de passe') }}</button></div>
</form>
@unless (auth()->user()->isAdmin())
<form method="post" action="{{ route('account.destroy') }}" class="panel border-danger" onsubmit="return confirm(@js(__('Supprimer définitivement votre compte et vos données ?')))">@csrf @method('delete')
  <h2 class="h5">{{ __('Supprimer mon compte') }}</h2><p class="small muted">{{ __('Vos données personnelles, favoris et abonnements sont effacés. Les factures sont conservées le temps exigé par la loi.') }}</p>
  <div class="d-flex gap-2"><input type="password" name="password_delete" class="form-control" required placeholder="{{ __('Mot de passe') }}" style="max-width:260px"><button class="btn btn-danger-soft">{{ __('Supprimer mon compte') }}</button></div>
</form>
@endunless
@endsection

@extends('layouts.app', ['title' => __('Lettre d\'information')])
@section('content')
<x-auth-card>
  @if ($state === 'confirmed')
    <div class="text-center"><i class="bi bi-envelope-heart" style="font-size:2.5rem;color:var(--ocre)"></i><h1 class="h4 mt-2">{{ __('Inscription confirmée') }}</h1><p class="muted">{{ __('Vous recevrez la prochaine lettre Trôofê.') }}</p></div>
  @elseif ($state === 'unsubscribed')
    <div class="text-center"><h1 class="h4">{{ __('Vous êtes désinscrit') }}</h1><p class="muted">{{ __('Vous ne recevrez plus la lettre Trôofê à :email.', ['email' => $sub->email]) }}</p>
      <form method="post" action="{{ route('newsletter.subscribe') }}">@csrf<input type="hidden" name="email" value="{{ $sub->email }}"><button class="btn btn-ghost btn-sm">{{ __('Je me suis trompé, me réinscrire') }}</button></form></div>
  @endif
  @if ($state !== 'unsubscribed')
    <form method="post" action="{{ route('newsletter.update', $sub->token) }}" class="mt-3">@csrf
      <h2 class="h6">{{ __('Mes préférences') }} — {{ $sub->email }}</h2>
      @foreach (config('troofe.newsletter_topics') as $k => $l)<label class="form-check"><input type="checkbox" class="form-check-input" name="topics[]" value="{{ $k }}" @checked($sub->wants($k))> {{ __($l) }}</label>@endforeach
      <div class="row g-2 mt-2"><div class="col"><select class="form-select form-select-sm" name="frequency" aria-label="{{ __('Fréquence') }}"><option value="weekly" @selected($sub->frequency === 'weekly')>{{ __('Hebdomadaire') }}</option><option value="monthly" @selected($sub->frequency === 'monthly')>{{ __('Mensuelle') }}</option></select></div>
        <div class="col"><select class="form-select form-select-sm" name="locale" aria-label="{{ __('Langue') }}"><option value="fr" @selected($sub->locale === 'fr')>Français</option><option value="en" @selected($sub->locale === 'en')>English</option></select></div></div>
      <div class="d-flex justify-content-between mt-3"><button class="btn btn-brand btn-sm">{{ __('Enregistrer') }}</button><a class="small" href="{{ route('newsletter.unsubscribe', $sub->token) }}">{{ __('Me désinscrire') }}</a></div>
    </form>
  @endif
</x-auth-card>
@endsection

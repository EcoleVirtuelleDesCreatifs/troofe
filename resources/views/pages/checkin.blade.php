@extends('layouts.app', ['title' => __('Contrôle d\'accès')])
@section('content')
<section class="section"><div class="wrap gutter" style="max-width:560px">
  <h1 class="h3">{{ __('Contrôle d\'accès') }}</h1><p class="muted">{{ __('Scannez le QR code avec un lecteur (le code s\'inscrit dans le champ) ou saisissez les 8 caractères imprimés sur le billet.') }}</p>
  @if ($r = session('checkin'))<div class="notice {{ $r[0] === 'ok' ? 'ok' : ($r[0] === 'warn' ? 'warn' : 'warn') }} mb-3 fs-5"><i class="bi {{ $r[0] === 'ok' ? 'bi-check-circle-fill' : 'bi-x-octagon-fill' }}"></i><div>{{ $r[1] }}</div></div>@endif
  <form method="post" action="{{ route('checkin.store') }}" class="panel d-flex gap-2">@csrf<input name="code" class="form-control form-control-lg mono" autofocus autocomplete="off" required placeholder="{{ __('Code du billet') }}"><button class="btn btn-brand btn-lg">{{ __('Valider') }}</button></form>
</div></section>
@endsection

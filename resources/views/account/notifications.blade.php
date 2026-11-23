@extends('account.layout', ['title' => __('Notifications'), 'subtitle' => __('Historique et préférences d\'envoi par e-mail')])
@section('actions')<form method="post" action="{{ route('account.notifications.readAll') }}">@csrf<button class="btn btn-ghost btn-sm">{{ __('Tout marquer comme lu') }}</button></form>@endsection
@section('account')
<form method="post" action="{{ route('account.notifications.preferences') }}" class="panel mb-4">@csrf @method('put')
  <h2 class="h5">{{ __('Recevoir par e-mail') }}</h2>
  <p class="small muted">{{ __('Toutes les notifications restent visibles ici. Les e-mails de sécurité, de facturation et de validation de vos fiches sont toujours envoyés.') }}</p>
  <div class="row g-2">
    @foreach (config('troofe.notification_categories') as $key => $cat)
      <div class="col-md-6"><div class="form-check form-switch"><input class="form-check-input" type="checkbox" role="switch" id="pref-{{ $key }}" name="mail[{{ $key }}]" value="1" @checked($cat['mandatory'] || auth()->user()->wantsMail($key)) @disabled($cat['mandatory'])><label class="form-check-label" for="pref-{{ $key }}">{{ __($cat['label']) }}@if ($cat['mandatory']) <span class="small muted">({{ __('obligatoire') }})</span>@endif</label></div></div>
    @endforeach
  </div>
  <button class="btn btn-brand btn-sm mt-3">{{ __('Enregistrer mes préférences') }}</button>
</form>
<div class="panel p-0">
  @forelse ($notifications as $n)
    <a href="{{ route('account.notifications.open', $n->id) }}" class="d-flex gap-3 p-3 border-bottom {{ $n->read_at ? '' : 'unread-row' }}"><i class="bi {{ $n->data['icon'] ?? 'bi-bell' }} fs-5" style="color:var(--ocre)"></i>
      <span class="min-w-0 flex-grow-1"><b class="d-block">{{ $n->data['title'] ?? '' }}</b><span class="small muted d-block">{{ $n->data['message'] ?? '' }}</span></span><span class="small muted text-nowrap">{{ $n->created_at->diffForHumans() }}</span></a>
  @empty
    <x-empty icon="bi-bell" :text="__('Aucune notification pour le moment.')" />
  @endforelse
</div>
<div class="mt-3">{{ $notifications->links() }}</div>
@endsection

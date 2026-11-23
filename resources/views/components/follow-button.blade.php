@props(['type', 'model'])
@php $on = $model->isFollowedBy(auth()->user()); $count = $model->follows()->count(); @endphp
@auth
<button type="button" class="btn {{ $on ? 'btn-soft' : 'btn-ghost' }}" data-toggle-url="{{ route('follows.toggle', [$type, $model->id]) }}" data-kind="follow" aria-pressed="{{ $on ? 'true' : 'false' }}"
  data-on="{{ __('Abonné') }}" data-off="{{ __('Suivre') }}" title="{{ __('Recevoir ses nouveautés par e-mail') }}"><i class="bi {{ $on ? 'bi-bell-fill' : 'bi-bell' }}"></i> <span>{{ $on ? __('Abonné') : __('Suivre') }}</span>@if ($count)<span class="small muted ms-1">· {{ $count }}</span>@endif</button>
@else
<a href="{{ route('login') }}" class="btn btn-ghost"><i class="bi bi-bell"></i> {{ __('Suivre') }}</a>
@endauth

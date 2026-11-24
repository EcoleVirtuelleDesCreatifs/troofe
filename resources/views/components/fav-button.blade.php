@props(['type', 'model', 'look' => 'fav-btn'])
@php $on = $model->isFavoritedBy(auth()->user()); @endphp
<button type="button" class="{{ $look }} {{ $on ? 'on' : '' }}" data-toggle-url="{{ route('favorites.toggle', [$type, $model->id]) }}" data-kind="fav" aria-pressed="{{ $on ? 'true' : 'false' }}" aria-label="{{ __('Ajouter aux favoris') }}"><i class="bi {{ $on ? 'bi-heart-fill' : 'bi-heart' }}"></i>{{ $slot }}</button>

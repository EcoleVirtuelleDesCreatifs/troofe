@props(['value' => 0])
<span class="stars" aria-label="{{ __(':n sur 5', ['n' => number_format($value, 1, ',', '')]) }}">@for ($i = 1; $i <= 5; $i++)<i class="bi {{ $value >= $i ? 'bi-star-fill' : ($value >= $i - .5 ? 'bi-star-half' : 'bi-star') }}"></i>@endfor</span>

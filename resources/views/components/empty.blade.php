@props(['icon' => 'bi-inbox', 'text'])
<div class="empty"><i class="bi {{ $icon }}"></i><p>{{ $text }}</p>{{ $slot }}</div>

@props(['user', 'size' => 36])
@if ($user?->avatar)
  <img src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($user->avatar) }}" alt="" class="rounded-circle" style="width:{{ $size }}px;height:{{ $size }}px;object-fit:cover">
@else
  <span class="avatar-i" style="width:{{ $size }}px;height:{{ $size }}px;font-size:{{ round($size * .38) }}px" aria-hidden="true">{{ $user?->initials() ?? '?' }}</span>
@endif

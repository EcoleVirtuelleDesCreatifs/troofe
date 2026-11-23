@extends('account.layout', ['title' => __('Mes favoris')])
@section('account')
@if ($favorites->isEmpty())<x-empty icon="bi-heart" :text="__('Ajoutez des restaurants, artistes ou événements à vos favoris avec le cœur.')" />@else
<div class="grid">@foreach ($favorites as $f)
  @php $m = $f->favoritable; @endphp
  @if ($m instanceof \App\Models\Restaurant)<x-restaurant-card :r="$m" />@elseif ($m instanceof \App\Models\Talent)<x-talent-card :talent="$m->load('offers')" />@elseif ($m instanceof \App\Models\Event)<x-event-card :event="$m->load('ticketTypes')" />@endif
@endforeach</div>@endif
@endsection

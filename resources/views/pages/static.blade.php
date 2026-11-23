@php
  $titles = ['mentions-legales' => __('Mentions légales'), 'confidentialite' => __('Politique de confidentialité'), 'conditions' => __('Conditions d\'utilisation'), 'a-propos' => __('À propos')];
@endphp
@extends('layouts.app', ['title' => $titles[$page]])
@section('content')
<section class="section"><div class="wrap gutter" style="max-width:820px">
  <h1 class="h2 mb-4">{{ $titles[$page] }}</h1>
  <div class="prose">@includeFirst(['pages.legal.'.$page.'-'.app()->getLocale(), 'pages.legal.'.$page.'-fr'])</div>
</div></section>
@endsection

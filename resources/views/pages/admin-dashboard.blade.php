<div>
  <div class="page-title"><div><h1>{{ __('Tableau de bord') }}</h1><p class="muted mb-0 mt-1">{{ __('Vue d’ensemble de la plateforme Trôofê') }}</p></div><span class="small muted">{{ now()->translatedFormat('l j F Y') }}</span></div>
  <div class="kpis mb-4">@foreach ($indicators as [$icon, $label, $value])<div class="kpi"><div class="lbl"><i class="bi {{ $icon }}"></i>{{ $label }}</div><div class="val tnum">{{ $value }}</div></div>@endforeach</div>
  <div class="row g-4">
    <div class="col-lg-5"><section class="panel h-100"><div class="panel-head"><h2 class="h5">{{ __('À traiter') }}</h2><span class="pill warn">{{ array_sum(array_column($pending, 1)) }}</span></div>
      @foreach ($pending as [$label, $count, $url])<a href="{{ $url }}" class="d-flex justify-content-between align-items-center py-3 border-bottom"><span>{{ $label }}</span><span class="pill {{ $count ? 'warn' : 'ok' }}">{{ $count }} {{ __('en attente') }}</span></a>@endforeach
    </section></div>
    <div class="col-lg-7"><section class="panel h-100"><div class="panel-head"><h2 class="h5">{{ __('Dernières réservations culture') }}</h2><a class="link-arrow" href="{{ \App\Filament\Admin\Resources\BookingResource::getUrl() }}">{{ __('Tout voir') }} <i class="bi bi-arrow-right"></i></a></div>
      @forelse ($bookings as $booking)<div class="d-flex justify-content-between gap-3 py-3 border-bottom"><div><b>{{ $booking->reference ?? '#'.$booking->id }}</b><div class="small muted">{{ $booking->created_at->translatedFormat('d M Y') }}</div></div><span class="pill">{{ $booking->status }}</span></div>@empty<p class="small muted mb-0">{{ __('Aucune réservation pour le moment.') }}</p>@endforelse
    </section></div>
  </div>
</div>

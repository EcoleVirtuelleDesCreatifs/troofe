<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}"><head><meta charset="utf-8"><title>{{ __('Facture') }} {{ $payment->invoice_number }}</title>
<style>
 body{ font-family: DejaVu Sans, sans-serif; font-size:12px; color:#2A1A12; }
 .head{ border-bottom:3px solid #5A1E0E; padding-bottom:12px; margin-bottom:24px; }
 .brand{ font-size:24px; font-weight:bold; color:#5A1E0E; } .muted{ color:#7A6A60; }
 table{ width:100%; border-collapse:collapse; margin-top:16px; } th,td{ padding:8px; border-bottom:1px solid #E6DCD2; text-align:left; } th{ background:#F6EEE6; }
 .right{ text-align:right; } .total td{ font-weight:bold; font-size:14px; border-top:2px solid #5A1E0E; }
 .stamp{ display:inline-block; border:2px solid #2F7A4A; color:#2F7A4A; padding:4px 10px; font-weight:bold; margin-top:12px; }
</style></head><body>
<div class="head"><table style="margin:0"><tr><td style="border:0"><div class="brand">Trôofê</div><div class="muted">{{ __('La maison numérique de la gastronomie africaine') }}<br>Abidjan, Côte d'Ivoire · {{ config('troofe.admin_email') }}</div></td>
<td style="border:0" class="right"><b style="font-size:18px">{{ __('FACTURE') }}</b><br>{{ __('N°') }} {{ $payment->invoice_number }}<br>{{ __('Date') }} : {{ $payment->paid_at->format('d/m/Y') }}</td></tr></table></div>
<p><b>{{ __('Facturé à') }}</b><br>{{ $payment->user->name }}<br>{{ $payment->user->email }}@if ($payment->user->phone)<br>{{ $payment->user->phone }}@endif @if ($payment->user->city)<br>{{ $payment->user->city }}, {{ $payment->user->country }}@endif</p>
<table><thead><tr><th>{{ __('Désignation') }}</th><th class="right">{{ __('Montant') }}</th></tr></thead><tbody>
  @if ($payment->payable instanceof \App\Models\Order)
    @foreach ($payment->payable->items as $i)<tr><td>{{ $i->quantity }} × {{ $i->name }}</td><td class="right">{{ money($i->total) }}</td></tr>@endforeach
    @if ($payment->payable->fees)<tr><td>{{ __('Livraison') }}</td><td class="right">{{ money($payment->payable->fees) }}</td></tr>@endif
  @else
    <tr><td>{{ $payment->description }}@if ($payment->payable instanceof \App\Models\Subscription)<br><span class="muted">{{ __('Période jusqu\'au :d', ['d' => $payment->payable->ends_at?->format('d/m/Y')]) }}</span>@endif</td><td class="right">{{ money($payment->amount) }}</td></tr>
  @endif
  <tr class="total"><td>{{ __('Total payé (TTC)') }}</td><td class="right">{{ money($payment->amount) }}</td></tr>
</tbody></table>
<div class="stamp">{{ __('PAYÉE') }} — {{ $payment->method }}</div>
<p class="muted" style="margin-top:24px">{{ __('Référence de transaction') }} : {{ $payment->reference }}<br>{{ __('Document généré électroniquement, valable sans signature.') }}</p>
</body></html>

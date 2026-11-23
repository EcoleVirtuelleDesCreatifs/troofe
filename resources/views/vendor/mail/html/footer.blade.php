<tr>
<td>
<table class="footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="content-cell" align="center">
{{ Illuminate\Mail\Markdown::parse($slot) }}
<p style="font-size:11px;color:#9A8A80;margin-top:8px">Trôofê · Abidjan · <a href="{{ url('/') }}" style="color:#9A8A80">{{ parse_url(config('app.url'), PHP_URL_HOST) }}</a> · <a href="{{ route('account.notifications') }}" style="color:#9A8A80">{{ __('Préférences de notification') }}</a></p>
</td>
</tr>
</table>
</td>
</tr>

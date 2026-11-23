{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:xhtml="http://www.w3.org/1999/xhtml" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
@foreach ($entries as $e)
  @php $sep = str_contains($e['loc'], '?') ? '&amp;' : '?'; @endphp
  <url>
    <loc>{{ $e['loc'] }}</loc>
    @if ($e['lastmod'])<lastmod>{{ $e['lastmod'] }}</lastmod>@endif
    <changefreq>{{ $e['freq'] }}</changefreq>
    <priority>{{ $e['priority'] }}</priority>
    <xhtml:link rel="alternate" hreflang="fr" href="{{ $e['loc'] }}"/>
    <xhtml:link rel="alternate" hreflang="en" href="{{ $e['loc'] }}{!! $sep !!}lang=en"/>
    <xhtml:link rel="alternate" hreflang="x-default" href="{{ $e['loc'] }}"/>
    @if ($e['image'])<image:image><image:loc>{{ $e['image'] }}</image:loc>@if ($e['title'])<image:title>{{ $e['title'] }}</image:title>@endif</image:image>@endif
  </url>
@endforeach
</urlset>

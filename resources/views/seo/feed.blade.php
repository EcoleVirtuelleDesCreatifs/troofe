{!! '<'.'?xml version="1.0" encoding="UTF-8"?>' !!}
<rss version="2.0" xmlns:atom="http://www.w3.org/2005/Atom">
<channel>
  <title>Trôofê</title>
  <link>{{ url('/') }}</link>
  <description>{{ __('La maison numérique de la gastronomie, de la culture et des savoir-faire africains.') }}</description>
  <language>{{ app()->getLocale() }}</language>
  <atom:link href="{{ route('feed') }}" rel="self" type="application/rss+xml"/>
  <lastBuildDate>{{ now()->toRssString() }}</lastBuildDate>
@foreach ($articles as $a)
  <item><title>{{ $a->tr('title') }}</title><link>{{ route('articles.show', $a) }}</link><guid isPermaLink="true">{{ route('articles.show', $a) }}</guid><pubDate>{{ $a->published_at->toRssString() }}</pubDate><category>{{ $a->category }}</category><description>{{ $a->tr('excerpt') }}</description></item>
@endforeach
@foreach ($events as $e)
  <item><title>{{ $e->tr('title') }} — {{ date_human($e->starts_at, 'D MMM YYYY') }}, {{ $e->city }}</title><link>{{ route('events.show', $e) }}</link><guid isPermaLink="true">{{ route('events.show', $e) }}</guid><pubDate>{{ $e->created_at->toRssString() }}</pubDate><category>{{ __('Agenda') }}</category><description>{{ $e->tr('excerpt') }}</description></item>
@endforeach
</channel>
</rss>

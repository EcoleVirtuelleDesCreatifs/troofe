<x-mail::message>
<p class="eyebrow">{{ __('BIENVENUE DANS LA COMMUNAUTÉ') }}</p>
<h1>{{ __('Votre prochaine découverte commence ici.') }}</h1>
<p class="greeting">{{ __('Bonjour :name,', ['name' => $name]) }}</p>
<p>{{ __('Votre compte Trôofê est confirmé. De la bonne table aux rencontres culturelles, retrouvez le meilleur de la gastronomie et des savoir-faire africains dans un seul espace.') }}</p>

<x-mail::button :url="route('home')">
{{ __('Découvrir Trôofê') }}
</x-mail::button>

<h2>{{ __('À découvrir sur Trôofê') }}</h2>
<table class="feature-list" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="feature-number" width="36">01</td>
<td><strong>{{ __('De bonnes adresses à partager') }}</strong><br><span class="feature-copy">{{ __('Explorez les restaurants, consultez les menus et commandez vos plats préférés.') }}</span></td>
</tr>
<tr>
<td class="feature-number" width="36">02</td>
<td><strong>{{ __('La culture, au plus près de vous') }}</strong><br><span class="feature-copy">{{ __('Rencontrez des artistes et des artisans, réservez une prestation ou vos prochains billets.') }}</span></td>
</tr>
<tr>
<td class="feature-number" width="36">03</td>
<td><strong>{{ __('Vos coups de cœur, à portée de main') }}</strong><br><span class="feature-copy">{{ __('Enregistrez vos favoris et suivez les nouveautés de vos adresses préférées.') }}</span></td>
</tr>
</table>

<x-mail::panel>
<strong>{{ __('Vous faites vivre la gastronomie ou la culture ?') }}</strong><br>
{{ __('Présentez votre activité et rejoignez les professionnels de Trôofê.') }}<br>
<a href="{{ route('pricing') }}">{{ __('Découvrir les offres professionnelles') }}</a>
</x-mail::panel>

<p class="signature">{{ __('Au plaisir de vous accompagner,') }}<br><strong>{{ __('L’équipe Trôofê') }}</strong></p>

<x-slot:subcopy>
{{ __('Le bouton ne s’affiche pas correctement ?') }} [{{ __('Ouvrir Trôofê dans mon navigateur') }}]({{ route('home') }})
</x-slot:subcopy>
</x-mail::message>

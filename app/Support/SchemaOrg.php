<?php

namespace App\Support;

use App\Models\Article;
use App\Models\Dish;
use App\Models\Event;
use App\Models\Restaurant;
use App\Models\Talent;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Données structurées schema.org (JSON-LD) : lisibles par Google (résultats
 * enrichis) et par les moteurs génératifs (ChatGPT, Perplexity, Gemini,
 * Claude) qui s'appuient sur des entités claires et des faits vérifiables.
 */
class SchemaOrg
{
    public static function id(string $url, string $frag): string
    {
        return $url.'#'.$frag;
    }

    private static function img(?string $url): ?string
    {
        return $url && ! str_contains($url, '/illustration/') ? $url : asset('assets/img/logo/png/logo-horizontal-1200.png');
    }

    private static function lang(): string
    {
        return app()->getLocale() === 'en' ? 'en' : 'fr';
    }

    private static function clean(?string $text, int $limit = 500): ?string
    {
        return $text ? Str::limit(trim(preg_replace('/\s+/', ' ', strip_tags($text))), $limit, '…') : null;
    }

    public static function organization(): array
    {
        return array_filter([
            '@type' => 'Organization',
            '@id' => url('/').'#organization',
            'name' => 'Trôofê',
            'url' => url('/'),
            'logo' => ['@type' => 'ImageObject', 'url' => asset('assets/img/logo/png/logo-horizontal-1200.png'), 'width' => 1200],
            'description' => __('La maison numérique de la gastronomie, de la culture et des savoir-faire africains.'),
            'email' => config('troofe.admin_email'),
            'areaServed' => ['Côte d\'Ivoire', 'Ghana', 'Sénégal', 'Nigeria', 'Cameroun', 'Bénin'],
            'knowsAbout' => ['Gastronomie africaine', 'Restaurants africains', 'Cuisine ivoirienne', 'Danses traditionnelles', 'Artisanat africain', 'Événements culinaires'],
            'sameAs' => array_values(array_filter(explode(',', (string) config('troofe.social_profiles')))),
        ]);
    }

    public static function website(): array
    {
        return [
            '@type' => 'WebSite',
            '@id' => url('/').'#website',
            'url' => url('/'),
            'name' => 'Trôofê',
            'inLanguage' => ['fr', 'en'],
            'publisher' => ['@id' => url('/').'#organization'],
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => ['@type' => 'EntryPoint', 'urlTemplate' => route('search').'?q={search_term_string}'],
                'query-input' => 'required name=search_term_string',
            ],
        ];
    }

    /** @param array<string,?string> $items libellé => URL (null = page courante) */
    public static function breadcrumbs(array $items): array
    {
        $list = [['@type' => 'ListItem', 'position' => 1, 'name' => __('Accueil'), 'item' => url('/')]];
        $i = 2;
        foreach ($items as $label => $url) {
            $list[] = array_filter(['@type' => 'ListItem', 'position' => $i++, 'name' => $label, 'item' => $url ?? url()->current()]);
        }

        return ['@type' => 'BreadcrumbList', '@id' => url()->current().'#breadcrumb', 'itemListElement' => $list];
    }

    public static function faq(array $pairs): array
    {
        return [
            '@type' => 'FAQPage',
            '@id' => url()->current().'#faq',
            'inLanguage' => self::lang(),
            'mainEntity' => array_map(fn ($p) => ['@type' => 'Question', 'name' => $p[0], 'acceptedAnswer' => ['@type' => 'Answer', 'text' => $p[1]]], $pairs),
        ];
    }

    /** Liste d'éléments pour les pages d'annuaire */
    public static function itemList(Collection $items, callable $url, callable $name): array
    {
        return [
            '@type' => 'ItemList',
            '@id' => url()->current().'#list',
            'numberOfItems' => $items->count(),
            'itemListElement' => $items->values()->map(fn ($m, $i) => ['@type' => 'ListItem', 'position' => $i + 1, 'url' => $url($m), 'name' => $name($m)])->all(),
        ];
    }

    private static function rating($model): ?array
    {
        $n = $model->ratingCount();

        return $n ? ['@type' => 'AggregateRating', 'ratingValue' => $model->ratingAverage(), 'reviewCount' => $n, 'bestRating' => 5, 'worstRating' => 1] : null;
    }

    private static function reviews($model, int $take = 5): array
    {
        return $model->publishedReviews()->with('user')->take($take)->get()->map(fn ($v) => [
            '@type' => 'Review',
            'author' => ['@type' => 'Person', 'name' => $v->user->name],
            'datePublished' => $v->created_at->toDateString(),
            'reviewRating' => ['@type' => 'Rating', 'ratingValue' => $v->rating, 'bestRating' => 5],
            'reviewBody' => self::clean($v->body, 400),
        ])->all();
    }

    private static function address(?string $street, ?string $district, string $city, string $country): array
    {
        return array_filter(['@type' => 'PostalAddress', 'streetAddress' => $street, 'addressLocality' => $city, 'addressRegion' => $district, 'addressCountry' => self::countryCode($country)]);
    }

    public static function countryCode(string $country): string
    {
        return ['Côte d\'Ivoire' => 'CI', 'Ghana' => 'GH', 'Sénégal' => 'SN', 'Nigeria' => 'NG', 'Cameroun' => 'CM', 'Bénin' => 'BJ', 'Togo' => 'TG', 'Mali' => 'ML', 'Burkina Faso' => 'BF'][$country] ?? $country;
    }

    private static function hours(?array $hours): array
    {
        $map = ['Lundi' => 'Monday', 'Mardi' => 'Tuesday', 'Mercredi' => 'Wednesday', 'Jeudi' => 'Thursday', 'Vendredi' => 'Friday', 'Samedi' => 'Saturday', 'Dimanche' => 'Sunday'];
        $out = [];
        foreach ($hours ?? [] as $day => $range) {
            if (preg_match('/(\d{1,2}[:h]\d{2})\s*[–-]\s*(\d{1,2}[:h]\d{2})/u', (string) $range, $m) && isset($map[$day])) {
                $out[] = ['@type' => 'OpeningHoursSpecification', 'dayOfWeek' => 'https://schema.org/'.$map[$day], 'opens' => str_replace('h', ':', $m[1]), 'closes' => str_replace('h', ':', $m[2])];
            }
        }

        return $out;
    }

    public static function restaurant(Restaurant $r): array
    {
        $url = route('restaurants.show', $r);
        $menu = $r->menuItems->groupBy('category');

        return array_filter([
            '@type' => 'Restaurant',
            '@id' => self::id($url, 'restaurant'),
            'name' => $r->name,
            'url' => $url,
            'description' => self::clean($r->tr('description')),
            'image' => self::img($r->coverUrl()),
            'servesCuisine' => [$r->cuisine, 'Africaine'],
            'priceRange' => str_repeat('$', max(1, (int) $r->price_level)),
            'currenciesAccepted' => 'XOF',
            'paymentAccepted' => 'Cash, Mobile Money, Credit Card',
            'telephone' => $r->phone,
            'address' => self::address($r->address, $r->district, $r->city, $r->country),
            'geo' => $r->lat ? ['@type' => 'GeoCoordinates', 'latitude' => (float) $r->lat, 'longitude' => (float) $r->lng] : null,
            'openingHoursSpecification' => self::hours($r->opening_hours) ?: null,
            'acceptsReservations' => false,
            'aggregateRating' => self::rating($r),
            'review' => self::reviews($r) ?: null,
            'hasMenu' => $menu->isNotEmpty() ? [
                '@type' => 'Menu',
                'name' => __('La carte'),
                'hasMenuSection' => $menu->map(fn ($items, $cat) => [
                    '@type' => 'MenuSection', 'name' => __($cat),
                    'hasMenuItem' => $items->map(fn ($m) => array_filter(['@type' => 'MenuItem', 'name' => $m->tr('name'), 'description' => self::clean($m->tr('description'), 200),
                        'offers' => ['@type' => 'Offer', 'price' => $m->price, 'priceCurrency' => 'XOF', 'availability' => 'https://schema.org/InStock']]))->values()->all(),
                ])->values()->all(),
            ] : null,
            'potentialAction' => $r->accepts_orders ? ['@type' => 'OrderAction', 'target' => $url.'#carte', 'deliveryMethod' => $r->delivery ? ['http://purl.org/goodrelations/v1#DeliveryModeOwnFleet', 'http://purl.org/goodrelations/v1#DeliveryModePickUp'] : 'http://purl.org/goodrelations/v1#DeliveryModePickUp'] : null,
            'isPartOf' => ['@id' => url('/').'#website'],
        ]);
    }

    public static function event(Event $e): array
    {
        $url = route('events.show', $e);
        $types = $e->ticketTypes;

        return array_filter([
            '@type' => match ($e->category) { 'Festival' => 'Festival', 'Concert', 'Dîner-concert' => 'MusicEvent', 'Atelier', 'Conférence' => 'EducationEvent', default => 'FoodEvent' },
            '@id' => self::id($url, 'event'),
            'name' => $e->tr('title'),
            'url' => $url,
            'description' => self::clean($e->tr('excerpt') ?: $e->tr('description')),
            'image' => self::img($e->coverUrl()),
            'startDate' => $e->starts_at->toIso8601String(),
            'endDate' => ($e->ends_at ?? $e->starts_at->copy()->addHours(3))->toIso8601String(),
            'eventStatus' => $e->status === 'suspended' ? 'https://schema.org/EventCancelled' : 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'inLanguage' => self::lang(),
            'location' => ['@type' => 'Place', 'name' => $e->venue, 'address' => self::address(null, null, $e->city, $e->country)],
            'organizer' => $e->organizer ? ['@type' => 'Organization', 'name' => $e->organizer->name, 'url' => url('/')] : ['@id' => url('/').'#organization'],
            'performer' => $e->performers->where('status', 'published')->map(fn ($t) => ['@type' => $t->type === 'troupe' ? 'PerformingGroup' : 'Person', 'name' => $t->name, 'url' => $t->url()])->values()->all() ?: null,
            'offers' => $types->isNotEmpty() ? $types->map(fn ($t) => [
                '@type' => 'Offer', 'name' => $t->name, 'price' => $t->price, 'priceCurrency' => 'XOF', 'url' => $url,
                'availability' => $t->remaining() > 0 ? 'https://schema.org/InStock' : 'https://schema.org/SoldOut',
                'validFrom' => $e->created_at?->toIso8601String(),
            ])->values()->all() : ['@type' => 'Offer', 'price' => 0, 'priceCurrency' => 'XOF', 'url' => $url, 'availability' => 'https://schema.org/InStock'],
            'isAccessibleForFree' => $types->isEmpty() || $types->max('price') === 0,
        ]);
    }

    public static function dish(Dish $d): array
    {
        $url = route('dishes.show', $d);

        return array_filter([
            '@type' => 'Recipe',
            '@id' => self::id($url, 'recipe'),
            'name' => $d->name,
            'url' => $url,
            'description' => self::clean($d->tr('summary')),
            'image' => self::img($d->imageUrl()),
            'recipeCuisine' => $d->origin_country,
            'recipeCategory' => $d->category,
            'keywords' => implode(', ', array_filter([$d->name, $d->origin_country, $d->region, 'cuisine africaine'])),
            'recipeIngredient' => $d->ingredients ?: null,
            'recipeInstructions' => $d->tr('recipe') ? collect(preg_split('/(?<=[.!?])\s+/', self::clean($d->tr('recipe'), 3000)))->filter()->map(fn ($s) => ['@type' => 'HowToStep', 'text' => $s])->values()->all() : null,
            'prepTime' => $d->prep_minutes ? 'PT'.$d->prep_minutes.'M' : null,
            'author' => ['@id' => url('/').'#organization'],
            'datePublished' => $d->created_at?->toDateString(),
            'inLanguage' => self::lang(),
            'isPartOf' => ['@id' => url('/').'#website'],
        ]);
    }

    public static function article(Article $a): array
    {
        $url = route('articles.show', $a);

        return array_filter([
            '@type' => 'NewsArticle',
            '@id' => self::id($url, 'article'),
            'headline' => Str::limit($a->tr('title'), 110, ''),
            'url' => $url,
            'mainEntityOfPage' => $url,
            'description' => self::clean($a->tr('excerpt')),
            'image' => [self::img($a->coverUrl())],
            'datePublished' => $a->published_at?->toIso8601String(),
            'dateModified' => $a->updated_at?->toIso8601String(),
            'articleSection' => $a->category,
            'wordCount' => str_word_count(strip_tags($a->tr('body'))),
            'inLanguage' => self::lang(),
            'author' => $a->author ? ['@type' => 'Person', 'name' => $a->author->name] : ['@id' => url('/').'#organization'],
            'publisher' => ['@id' => url('/').'#organization'],
        ]);
    }

    /** Troupe → PerformingGroup ; artiste → PerformingGroup ou Person ; artisan → LocalBusiness avec catalogue */
    public static function talent(Talent $t): array
    {
        $url = $t->url();
        $offers = $t->offers->map(fn ($o) => array_filter([
            '@type' => 'Offer',
            'name' => $o->tr('name'),
            'description' => self::clean($o->tr('details'), 200),
            'price' => $o->price,
            'priceCurrency' => 'XOF',
            'availability' => 'https://schema.org/InStock',
            'itemOffered' => $t->isCraft() ? ['@type' => 'Product', 'name' => $o->tr('name'), 'category' => $t->category] : ['@type' => 'Service', 'name' => $o->tr('name'), 'serviceType' => $t->category],
        ]))->values()->all();

        $type = match (true) {
            $t->isCraft() => 'LocalBusiness',
            $t->type === 'artiste' && ($t->members ?? 1) <= 1 => 'Person',
            default => 'PerformingGroup',
        };

        $base = array_filter([
            '@type' => $type,
            '@id' => self::id($url, 'entity'),
            'name' => $t->name,
            'url' => $url,
            'description' => self::clean($t->tr('description')),
            'image' => self::img($t->coverUrl()),
            'slogan' => $t->tr('tagline'),
            'knowsAbout' => array_values(array_filter([...($t->tags ?? []), ...($t->repertoire ?? [])])) ?: null,
            'foundingDate' => $type !== 'Person' && $t->since_year ? (string) $t->since_year : null,
            'telephone' => $t->phone,
            'aggregateRating' => self::rating($t),
            'review' => self::reviews($t) ?: null,
            'makesOffer' => $type === 'Person' ? null : $offers,
            'hasOfferCatalog' => $type === 'Person' ? ['@type' => 'OfferCatalog', 'name' => __('Formules'), 'itemListElement' => $offers] : null,
            'areaServed' => $t->zones ?: [$t->city],
            'numberOfEmployees' => $type === 'PerformingGroup' && $t->members ? ['@type' => 'QuantitativeValue', 'value' => $t->members + (int) $t->musicians] : null,
            'knowsLanguage' => $t->languages ?: null,
        ]);

        if ($type === 'Person') {
            $base['homeLocation'] = ['@type' => 'Place', 'address' => self::address(null, $t->district, $t->city, $t->country)];
            $base['jobTitle'] = $t->category;
        } else {
            $base['address'] = self::address(null, $t->district, $t->city, $t->country);
            if ($t->isCraft() && $t->visit_price !== null) {
                $base['potentialAction'] = ['@type' => 'ReserveAction', 'target' => $url.'#booking-form-wrap', 'name' => __('Visite de l\'atelier')];
            }
        }

        return $base;
    }
}

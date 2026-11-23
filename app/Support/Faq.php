<?php

namespace App\Support;

use App\Models\Dish;
use App\Models\Event;
use App\Models\Restaurant;
use App\Models\Talent;

/**
 * Questions-réponses factuelles générées à partir des données de chaque fiche.
 * Affichées sur la page et balisées FAQPage : c'est le format que les moteurs
 * de réponse (Google, ChatGPT, Perplexity, Gemini) citent le plus volontiers.
 */
class Faq
{
    public static function restaurant(Restaurant $r): array
    {
        $prices = $r->menuItems->pluck('price');
        $hours = collect($r->opening_hours ?? [])->map(fn ($h, $d) => __($d).' : '.$h)->implode(' ; ');

        return [
            [__('Où se trouve :name ?', ['name' => $r->name]), __(':name se trouve :address, à :city (:country).', ['name' => $r->name, 'address' => collect([$r->address, $r->district])->filter()->implode(', ') ?: __('dans le quartier'), 'city' => $r->city, 'country' => __($r->country)])],
            [__('Quels sont les horaires de :name ?', ['name' => $r->name]), $hours ?: null],
            [__('Quel est le prix moyen d\'un plat chez :name ?', ['name' => $r->name]), $prices->isNotEmpty() ? __('Les plats coûtent de :min à :max, soit :avg en moyenne.', ['min' => money($prices->min()), 'max' => money($prices->max()), 'avg' => money((int) round($prices->avg()))]) : null],
            [__(':name livre-t-il à domicile ?', ['name' => $r->name]), $r->accepts_orders ? ($r->delivery ? __('Oui. Commandez en ligne sur Trôofê : livraison (:fee) ou retrait sur place, paiement par Mobile Money ou carte.', ['fee' => money($r->delivery_fee)]) : __('La commande en ligne est disponible à emporter, avec paiement par Mobile Money ou carte. La livraison n\'est pas proposée.')) : __('La commande en ligne n\'est pas encore proposée.')],
            [__('Quelle cuisine sert :name ?', ['name' => $r->name]), __(':name propose une cuisine :cuisine.', ['name' => $r->name, 'cuisine' => mb_strtolower(__($r->cuisine))]).($r->dishes->isNotEmpty() ? ' '.__('Spécialités : :list.', ['list' => $r->dishes->pluck('name')->implode(', ')]) : '')],
            [__('Que pensent les clients de :name ?', ['name' => $r->name]), $r->ratingCount() ? __('Note moyenne de :avg/5 sur :n avis vérifiés.', ['avg' => number_format($r->ratingAverage(), 1, ',', ''), 'n' => $r->ratingCount()]) : null],
        ];
    }

    public static function talent(Talent $t): array
    {
        $p = $t->minPrice();
        $craft = $t->isCraft();

        return [
            [$craft ? __('Combien coûtent les créations de :name ?', ['name' => $t->name]) : __('Combien coûte une prestation de :name ?', ['name' => $t->name]),
                $p ? __('À partir de :price. :list', ['price' => money($p), 'list' => $t->offers->map(fn ($o) => $o->tr('name').' : '.money($o->price))->implode(' ; ').'.']) : __('Le tarif est établi sur devis.')],
            [$craft ? __('Quel est le délai de fabrication ?') : __('Combien de temps à l\'avance faut-il réserver :name ?', ['name' => $t->name]),
                $craft ? ($t->offers->pluck('lead_time')->filter()->unique()->implode(', ') ?: null) : trans_choice('Au moins :count jour avant la date de l\'événement.|Au moins :count jours avant la date de l\'événement.', $t->min_notice_days)],
            [__('Où intervient :name ?', ['name' => $t->name]), $craft ? __('L\'atelier est situé à :place (:country). Livraison possible, frais sur devis.', ['place' => trim(($t->district ? $t->district.', ' : '').$t->city), 'country' => __($t->country)]) : __('Basé à :city, :name intervient à : :zones.', ['city' => $t->city, 'name' => $t->name, 'zones' => implode(', ', $t->zones ?: [$t->city])])],
            [__('Pour quelles occasions réserver :name ?', ['name' => $t->name]), $t->audiences ? collect($t->audiences)->map(fn ($a) => __($a))->implode(', ').'.' : null],
            [__('Comment réserver ou commander ?'), __('Envoyez une demande depuis cette page : :name répond avec un devis, rien n\'est dû avant votre accord. Le suivi se fait dans votre espace Trôofê, avec des notifications par e-mail.', ['name' => $t->name])],
            [$craft ? __('Peut-on visiter l\'atelier ?') : null, $craft && $t->visit_price !== null ? __('Oui : :details (:duration), :price par personne.', ['details' => $t->visit_details, 'duration' => $t->visit_duration, 'price' => money($t->visit_price)]) : null],
            [__(':name est-il vérifié ?', ['name' => $t->name]), $t->is_verified ? __('Oui. L\'identité et les références de :name ont été vérifiées par l\'équipe Trôofê.', ['name' => $t->name]) : null],
        ];
    }

    public static function dish(Dish $d, $restaurants): array
    {
        return [
            [__('D\'où vient le :name ?', ['name' => $d->name]), strip_tags($d->tr('summary') ?: '') ?: __('Le :name est originaire de :country.', ['name' => $d->name, 'country' => __($d->origin_country)])],
            [__('Quels sont les ingrédients du :name ?', ['name' => $d->name]), $d->ingredients ? implode(', ', $d->ingredients).'.' : null],
            [__('Combien de temps faut-il pour préparer le :name ?', ['name' => $d->name]), $d->prep_minutes ? __('Environ :n minutes.', ['n' => $d->prep_minutes]) : null],
            [__('Où manger du :name ?', ['name' => $d->name]), $restaurants->isNotEmpty() ? __('Notamment chez :list, à commander sur Trôofê.', ['list' => $restaurants->map(fn ($r) => $r->name.' ('.$r->city.')')->implode(', ')]) : null],
        ];
    }

    public static function event(Event $e): array
    {
        $types = $e->ticketTypes;

        return [
            [__('Quand a lieu :event ?', ['event' => $e->tr('title')]), __('Le :date, de :from à :to.', ['date' => date_human($e->starts_at), 'from' => $e->starts_at->format('H:i'), 'to' => ($e->ends_at ?? $e->starts_at->copy()->addHours(3))->format('H:i')])],
            [__('Où se déroule :event ?', ['event' => $e->tr('title')]), $e->venue.', '.$e->city.' ('.__($e->country).').'],
            [__('Combien coûte l\'entrée ?'), $types->isEmpty() || $types->max('price') === 0 ? __('L\'entrée est gratuite.') : $types->map(fn ($t) => $t->name.' : '.money($t->price))->implode(' ; ').'.'],
            [__('Comment obtenir mon billet ?'), $types->isNotEmpty() ? __('Réservez sur cette page et payez par Mobile Money ou carte. Le billet nominatif avec QR code arrive par e-mail, avec un rappel la veille.') : null],
        ];
    }
}

<?php

namespace App\Notifications\Billing;

use App\Models\Subscription;
use App\Notifications\TroofeNotification;

class SubscriptionExpired extends TroofeNotification
{
    public function __construct(public Subscription $subscription) {}

    public function category(): string { return 'facturation'; }

    public function title(): string { return __('Votre abonnement :plan a expiré', ['plan' => $this->subscription->plan->tr('name')]); }

    public function message(): string { return __('Votre compte repasse en formule Découverte. Les fiches au-delà de la limite gratuite sont masquées jusqu\'au renouvellement ; aucune donnée n\'est supprimée.'); }

    public function url(): string { return route('pricing'); }

    public function icon(): string { return 'bi-hourglass-bottom'; }

    protected function actionLabel(): string { return __('Choisir une formule'); }
}

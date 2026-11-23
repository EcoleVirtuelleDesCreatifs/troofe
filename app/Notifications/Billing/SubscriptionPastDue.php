<?php

namespace App\Notifications\Billing;

use App\Models\Subscription;
use App\Notifications\TroofeNotification;

class SubscriptionPastDue extends TroofeNotification
{
    public function __construct(public Subscription $subscription, public int $graceDays) {}

    public function category(): string { return 'facturation'; }

    public function title(): string { return __('Échéance dépassée : renouvelez votre abonnement'); }

    public function message(): string { return __('Votre formule :plan est arrivée à échéance. Vous disposez de :days jours pour la renouveler avant que vos avantages ne soient suspendus.', ['plan' => $this->subscription->plan->tr('name'), 'days' => $this->graceDays]); }

    public function url(): string { return route('subscription.renew', $this->subscription); }

    public function icon(): string { return 'bi-exclamation-triangle'; }

    protected function actionLabel(): string { return __('Renouveler maintenant'); }
}

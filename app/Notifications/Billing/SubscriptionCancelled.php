<?php

namespace App\Notifications\Billing;

use App\Models\Subscription;
use App\Notifications\TroofeNotification;

class SubscriptionCancelled extends TroofeNotification
{
    public function __construct(public Subscription $subscription) {}

    public function category(): string { return 'facturation'; }

    public function title(): string { return __('Résiliation enregistrée'); }

    public function message(): string { return __('Votre formule :plan ne sera pas renouvelée. Vous en profitez jusqu\'au :date. Vous pouvez la réactiver à tout moment avant cette date.', ['plan' => $this->subscription->plan->tr('name'), 'date' => date_human($this->subscription->ends_at)]); }

    public function url(): string { return route('account.billing'); }

    public function icon(): string { return 'bi-x-circle'; }
}

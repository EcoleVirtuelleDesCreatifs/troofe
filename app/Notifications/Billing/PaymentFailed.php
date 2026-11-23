<?php

namespace App\Notifications\Billing;

use App\Models\Payment;
use App\Notifications\TroofeNotification;

class PaymentFailed extends TroofeNotification
{
    public function __construct(public Payment $payment) {}

    public function category(): string { return 'facturation'; }

    public function title(): string { return __('Le paiement n\'a pas abouti'); }

    public function message(): string { return __('Le paiement de :amount pour « :description » a échoué ou a été annulé. Aucun montant n\'a été débité. Vous pouvez réessayer.', ['amount' => money($this->payment->amount), 'description' => $this->payment->description]); }

    public function url(): string { return route('payments.retry', $this->payment); }

    public function icon(): string { return 'bi-credit-card'; }

    protected function actionLabel(): string { return __('Réessayer le paiement'); }
}

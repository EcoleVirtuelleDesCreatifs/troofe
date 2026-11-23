<?php

namespace App\Services\Payments;

use App\Models\Payment;

interface PaymentGateway
{
    /** Démarre le paiement et renvoie l'URL de la page de paiement */
    public function initiate(Payment $payment): string;

    /**
     * Interroge le prestataire sur l'état réel de la transaction.
     *
     * @return array{status: 'paid'|'failed'|'pending', method: ?string, payload: array}
     */
    public function verify(Payment $payment): array;
}

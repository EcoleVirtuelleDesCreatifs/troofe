<?php

namespace App\Services\Payments;

use App\Models\Payment;

/**
 * Passerelle de test : une page interne permet de valider ou refuser le
 * paiement. Aucune somme n'est débitée. À n'utiliser qu'en recette.
 */
class SimulationGateway implements PaymentGateway
{
    public function initiate(Payment $payment): string
    {
        return route('payments.simulate', $payment);
    }

    public function verify(Payment $payment): array
    {
        $decision = $payment->provider_payload['simulation'] ?? null;

        return [
            'status' => $decision === 'paid' ? 'paid' : ($decision === 'failed' ? 'failed' : 'pending'),
            'method' => $payment->provider_payload['method'] ?? 'SIMULATION',
            'payload' => ['simulation' => $decision],
        ];
    }
}

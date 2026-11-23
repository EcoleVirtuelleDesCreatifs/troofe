<?php

namespace App\Services\Payments;

use App\Models\Payment;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * CinetPay : Orange Money, MTN MoMo, Moov Money, Wave et cartes bancaires
 * (Côte d'Ivoire, Sénégal, Mali, Burkina, Togo, Bénin, Cameroun…).
 * Documentation : https://docs.cinetpay.com/api/1.0-fr/checkout/initialisation
 */
class CinetPayGateway implements PaymentGateway
{
    private function cfg(string $key): string
    {
        $value = (string) config("services.cinetpay.$key");
        if ($value === '') {
            throw new RuntimeException("CinetPay : la variable « $key » n'est pas configurée (.env).");
        }

        return $value;
    }

    public function initiate(Payment $payment): string
    {
        $user = $payment->user;
        // CinetPay exige un montant multiple de 5 pour le XOF
        $amount = (int) (ceil($payment->amount / 5) * 5);

        $response = Http::timeout(30)->acceptJson()->post(config('services.cinetpay.endpoint').'/payment', [
            'apikey' => $this->cfg('api_key'),
            'site_id' => $this->cfg('site_id'),
            'transaction_id' => $payment->reference,
            'amount' => $amount,
            'currency' => $payment->currency,
            'description' => mb_substr(preg_replace('/[^\pL\pN \-]/u', ' ', $payment->description), 0, 120),
            'notify_url' => route('payments.notify', 'cinetpay'),
            'return_url' => route('payments.return', $payment),
            'channels' => 'ALL',
            'lang' => app()->getLocale() === 'en' ? 'EN' : 'FR',
            'customer_id' => (string) $user->id,
            'customer_name' => $user->name,
            'customer_surname' => $user->name,
            'customer_email' => $user->email,
            'customer_phone_number' => preg_replace('/\D/', '', (string) $user->phone) ?: null,
            'metadata' => (string) $payment->id,
        ]);

        $url = $response->json('data.payment_url');
        if (! $response->successful() || ! $url) {
            $payment->update(['status' => 'failed', 'provider_payload' => $response->json()]);
            throw new RuntimeException(__('Le service de paiement est momentanément indisponible. Réessayez dans quelques minutes.'));
        }

        $payment->update(['provider_payload' => ['init' => $response->json('data')]]);

        return $url;
    }

    public function verify(Payment $payment): array
    {
        $response = Http::timeout(30)->acceptJson()->post(config('services.cinetpay.endpoint').'/payment/check', [
            'apikey' => $this->cfg('api_key'),
            'site_id' => $this->cfg('site_id'),
            'transaction_id' => $payment->reference,
        ]);

        $data = (array) $response->json('data', []);
        $status = match (strtoupper((string) ($data['status'] ?? ''))) {
            'ACCEPTED' => 'paid',
            'REFUSED', 'CANCELED', 'CANCELLED' => 'failed',
            default => 'pending',
        };

        // Sécurité : le montant payé doit couvrir le montant attendu
        if ($status === 'paid' && (int) ($data['amount'] ?? 0) < $payment->amount) {
            $status = 'failed';
        }

        return ['status' => $status, 'method' => $data['payment_method'] ?? null, 'payload' => $data];
    }
}

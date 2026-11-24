<?php

namespace App\Services;

use App\Models\AdCampaign;
use App\Models\ActivityLog;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Subscription;
use App\Models\User;
use App\Notifications\Ads\CampaignStatusChanged;
use App\Notifications\Billing\PaymentFailed;
use App\Notifications\Billing\PaymentReceived;
use App\Notifications\Billing\SubscriptionActivated;
use App\Services\Payments\CinetPayGateway;
use App\Services\Payments\PaymentGateway;
use App\Services\Payments\SimulationGateway;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PaymentService
{
    public function __construct(private OrderService $orders) {}

    public function gateway(?string $provider = null): PaymentGateway
    {
        $provider ??= config('services.payments.driver');
        abort_if($provider === 'simulation' && app()->isProduction(), 503, __('Les paiements de démonstration sont désactivés en production.'));

        return match ($provider) {
            'cinetpay' => app(CinetPayGateway::class),
            'simulation' => app(SimulationGateway::class),
            default => throw new \InvalidArgumentException('Unsupported payment provider.'),
        };
    }

    /** Crée le paiement et renvoie l'URL vers laquelle rediriger le client */
    public function start(User $user, Model $payable, int $amount, string $description): string
    {
        $gateway = $amount === 0 ? null : $this->gateway();
        $payment = Payment::create([
            'reference' => 'TRF'.now()->format('ymd').strtoupper(Str::random(8)),
            'user_id' => $user->id,
            'payable_type' => $payable->getMorphClass(),
            'payable_id' => $payable->getKey(),
            'description' => $description,
            'amount' => $amount,
            'currency' => config('troofe.currency'),
            'provider' => config('services.payments.driver'),
            'status' => 'pending',
        ]);

        // Montant nul (billets gratuits, offre à 0) : validation immédiate
        if ($amount === 0) {
            $this->markPaid($payment, 'GRATUIT', []);

            return route('payments.return', $payment);
        }

        return $gateway->initiate($payment);
    }

    /** Vérifie l'état auprès du prestataire et applique le résultat (idempotent) */
    public function sync(Payment $payment): Payment
    {
        if ($payment->status !== 'pending') {
            return $payment;
        }
        $result = $this->gateway($payment->provider)->verify($payment);

        match ($result['status']) {
            'paid' => $this->markPaid($payment, $result['method'], $result['payload']),
            'failed' => $this->markFailed($payment, $result['payload']),
            default => null,
        };

        return $payment->fresh();
    }

    public function markPaid(Payment $payment, ?string $method, array $payload): void
    {
        $applied = DB::transaction(function () use ($payment, $method, $payload) {
            $locked = Payment::whereKey($payment->id)->lockForUpdate()->first();
            if ($locked->status === 'paid') {
                return false;
            }
            $locked->update([
                'status' => 'paid',
                'method' => $method,
                'paid_at' => now(),
                'provider_payload' => array_merge($locked->provider_payload ?? [], ['result' => $payload]),
            ]);
            $locked->assignInvoiceNumber();
            $this->fulfil($locked->payable);

            return true;
        });

        if ($applied) {
            $payment->refresh();
            ActivityLog::record('Paiement reçu', $payment, money($payment->amount).' — '.$payment->description);
            if ($payment->amount > 0) {
                $payment->user->notify(new PaymentReceived($payment));
            }
        }
    }

    public function markFailed(Payment $payment, array $payload): void
    {
        $applied = DB::transaction(function () use ($payment, $payload) {
            $locked = Payment::whereKey($payment->id)->lockForUpdate()->firstOrFail();
            if ($locked->status !== 'pending') {
                return false;
            }
            $locked->update(['status' => 'failed', 'provider_payload' => array_merge($locked->provider_payload ?? [], ['result' => $payload])]);
            if ($locked->payable instanceof Order) {
                $this->orders->releaseStock($locked->payable);
            }

            return true;
        });
        $payment->refresh();
        if ($applied) {
            $payment->user->notify(new PaymentFailed($payment));
        }
    }

    /** Effets du paiement selon l'objet payé */
    private function fulfil(Model $payable): void
    {
        if ($payable instanceof Subscription) {
            $renewal = $payable->status !== 'pending';
            // Un seul abonnement actif : l'ancien (autre formule) est clôturé
            Subscription::where('user_id', $payable->user_id)->where('id', '!=', $payable->id)
                ->whereIn('status', ['active', 'past_due', 'cancelled'])->update(['status' => 'expired', 'auto_renew' => false]);
            $payable->activateForPeriod();
            app(ListingQuota::class)->restore($payable->user->fresh());
            DB::afterCommit(fn () => $payable->user->notify(new SubscriptionActivated($payable->fresh('plan'), $renewal)));
        } elseif ($payable instanceof Order) {
            $this->orders->fulfil($payable);
        } elseif ($payable instanceof AdCampaign) {
            $payable->update(['status' => 'active']);
            DB::afterCommit(fn () => $payable->user?->notify(new CampaignStatusChanged($payable)));
        }
    }
}

<?php

namespace App\Notifications\Billing;

use App\Models\Subscription;
use App\Notifications\TroofeNotification;
use Illuminate\Notifications\Messages\MailMessage;

class SubscriptionRenewalReminder extends TroofeNotification
{
    public function __construct(public Subscription $subscription, public int $days) {}

    public function category(): string { return 'facturation'; }

    public function title(): string
    {
        return trans_choice('{1} Votre abonnement arrive à échéance demain|[2,*] Votre abonnement arrive à échéance dans :count jours', $this->days, ['count' => $this->days]);
    }

    public function message(): string
    {
        return __('Votre formule :plan prend fin le :date. Renouvelez-la pour garder vos fiches en ligne et vos avantages.', [
            'plan' => $this->subscription->plan->tr('name'),
            'date' => date_human($this->subscription->ends_at),
        ]);
    }

    public function url(): string { return route('subscription.renew', $this->subscription); }

    public function icon(): string { return 'bi-alarm'; }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->mailBase($notifiable)->line($this->message())
            ->line(__('Montant du renouvellement : :amount (:cycle).', ['amount' => money($this->subscription->amount()), 'cycle' => __($this->subscription->cycleLabel())]))
            ->action(__('Renouveler maintenant'), $this->url())
            ->line(__('Paiement par Orange Money, MTN MoMo, Moov Money, Wave ou carte bancaire.'));
    }
}

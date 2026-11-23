<?php

namespace App\Notifications\Billing;

use App\Models\Subscription;
use App\Notifications\TroofeNotification;
use Illuminate\Notifications\Messages\MailMessage;

class SubscriptionActivated extends TroofeNotification
{
    public function __construct(public Subscription $subscription, public bool $renewal = false) {}

    public function category(): string { return 'facturation'; }

    public function title(): string
    {
        return $this->renewal ? __('Votre abonnement :plan est renouvelé', ['plan' => $this->subscription->plan->tr('name')])
            : __('Votre abonnement :plan est actif', ['plan' => $this->subscription->plan->tr('name')]);
    }

    public function message(): string
    {
        return __('Formule :plan (:cycle), valable jusqu\'au :date.', [
            'plan' => $this->subscription->plan->tr('name'),
            'cycle' => __($this->subscription->cycleLabel()),
            'date' => date_human($this->subscription->ends_at),
        ]);
    }

    public function url(): string { return route('account.billing'); }

    public function icon(): string { return 'bi-patch-check'; }

    public function toMail(object $notifiable): MailMessage
    {
        $plan = $this->subscription->plan;
        $mail = $this->mailBase($notifiable)->line($this->message())->line(__('Ce qui est inclus :'));
        foreach (($plan->tr('features') ?? []) as $f) {
            $mail->line('• '.$f);
        }

        return $mail->action(__('Ouvrir mon espace pro'), url('/pro'))
            ->line(__('Votre facture est disponible dans « Abonnement et factures ».'));
    }
}

<?php

namespace App\Notifications\Billing;

use App\Models\Payment;
use App\Notifications\TroofeNotification;
use Illuminate\Notifications\Messages\MailMessage;

class PaymentReceived extends TroofeNotification
{
    public function __construct(public Payment $payment) {}

    public function category(): string { return 'facturation'; }

    public function title(): string { return __('Paiement reçu : :amount', ['amount' => money($this->payment->amount)]); }

    public function message(): string { return __(':description — facture :number.', ['description' => $this->payment->description, 'number' => $this->payment->invoice_number]); }

    public function url(): string { return route('account.invoice', $this->payment); }

    public function icon(): string { return 'bi-receipt'; }

    protected function actionLabel(): string { return __('Télécharger la facture'); }

    public function toMail(object $notifiable): MailMessage
    {
        return $this->mailBase($notifiable)
            ->line(__('Nous avons bien reçu votre paiement. Merci !'))
            ->line(__('Objet : :description', ['description' => $this->payment->description]))
            ->line(__('Montant : :amount', ['amount' => money($this->payment->amount)]))
            ->line(__('Moyen de paiement : :method', ['method' => $this->payment->method ?: $this->payment->provider]))
            ->line(__('Référence : :ref', ['ref' => $this->payment->reference]))
            ->action($this->actionLabel(), $this->url());
    }
}

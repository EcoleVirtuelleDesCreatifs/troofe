<?php

namespace App\Notifications\Commerce;

use App\Models\Order;
use App\Notifications\TroofeNotification;
use Illuminate\Notifications\Messages\MailMessage;

/** Au client : commande payée et transmise */
class OrderPlaced extends TroofeNotification
{
    public function __construct(public Order $order) {}

    public function category(): string { return 'commandes'; }

    public function title(): string { return __('Commande :ref confirmée', ['ref' => $this->order->reference]); }

    public function message(): string { return __('Votre commande chez :place (:amount) est payée et transmise. Vous serez prévenu à chaque étape.', ['place' => $this->order->restaurant?->name, 'amount' => money($this->order->total)]); }

    public function url(): string { return route('account.orders.show', $this->order); }

    public function icon(): string { return 'bi-bag-check'; }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = $this->mailBase($notifiable)->line($this->message());
        foreach ($this->order->items as $i) {
            $mail->line("• {$i->quantity} × {$i->name} — ".money($i->total));
        }
        if ($this->order->fees) {
            $mail->line(__('Frais de livraison : :amount', ['amount' => money($this->order->fees)]));
        }

        return $mail->line(__('Total : :amount', ['amount' => money($this->order->total)]))
            ->action(__('Suivre ma commande'), $this->url());
    }
}

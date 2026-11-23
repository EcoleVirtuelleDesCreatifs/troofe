<?php

namespace App\Notifications\Commerce;

use App\Models\Order;
use App\Notifications\TroofeNotification;
use Illuminate\Notifications\Messages\MailMessage;

/** Au restaurateur : nouvelle commande payée */
class NewOrderReceived extends TroofeNotification
{
    public function __construct(public Order $order) {}

    public function category(): string { return 'commandes'; }

    public function title(): string { return __('Nouvelle commande :ref', ['ref' => $this->order->reference]); }

    public function message(): string { return __(':client a commandé pour :amount (:mode).', ['client' => $this->order->user->name, 'amount' => money($this->order->total), 'mode' => __($this->order->fulfilment === 'livraison' ? 'livraison' : 'à emporter')]); }

    public function url(): string { return url('/pro/orders'); }

    public function icon(): string { return 'bi-bell'; }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = $this->mailBase($notifiable)->line($this->message());
        foreach ($this->order->items as $i) {
            $mail->line("• {$i->quantity} × {$i->name}");
        }
        if ($this->order->note) {
            $mail->line(__('Note du client : :note', ['note' => $this->order->note]));
        }
        if ($this->order->delivery_address) {
            $mail->line(__('Adresse : :address', ['address' => $this->order->delivery_address]));
        }

        return $mail->line(__('Téléphone : :phone', ['phone' => $this->order->contact_phone]))
            ->action(__('Gérer la commande'), $this->url());
    }
}

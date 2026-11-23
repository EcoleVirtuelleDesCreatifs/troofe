<?php

namespace App\Notifications\Commerce;

use App\Models\Order;
use App\Notifications\TroofeNotification;

class OrderStatusChanged extends TroofeNotification
{
    public function __construct(public Order $order) {}

    public function category(): string { return 'commandes'; }

    public function title(): string { return __('Commande :ref : :status', ['ref' => $this->order->reference, 'status' => mb_strtolower(__($this->order->statusLabel()))]); }

    public function message(): string
    {
        return match ($this->order->status) {
            'confirmee' => __(':place a accepté votre commande.', ['place' => $this->order->restaurant?->name]),
            'en_preparation' => __('Votre commande est en cuisine.'),
            'prete' => $this->order->fulfilment === 'livraison' ? __('Votre commande est prête et part en livraison.') : __('Votre commande est prête : vous pouvez venir la récupérer.'),
            'terminee', 'livree' => __('Bon appétit ! Donnez votre avis sur :place.', ['place' => $this->order->restaurant?->name]),
            'annulee' => __('Votre commande a été annulée. Le remboursement est effectué sur votre moyen de paiement.'),
            default => __('Le statut de votre commande a changé.'),
        };
    }

    public function url(): string { return route('account.orders.show', $this->order); }

    public function icon(): string { return 'bi-truck'; }
}

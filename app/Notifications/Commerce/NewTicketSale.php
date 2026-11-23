<?php

namespace App\Notifications\Commerce;

use App\Models\Order;
use App\Notifications\TroofeNotification;

class NewTicketSale extends TroofeNotification
{
    public function __construct(public Order $order) {}

    public function category(): string { return 'commandes'; }

    public function title(): string { return __('Vente de billets : :event', ['event' => $this->order->event->title]); }

    public function message(): string { return __(':client a acheté :count billet(s) pour :amount.', ['client' => $this->order->user->name, 'count' => $this->order->tickets->count(), 'amount' => money($this->order->total)]); }

    public function url(): string { return url('/pro/events'); }

    public function icon(): string { return 'bi-graph-up'; }
}

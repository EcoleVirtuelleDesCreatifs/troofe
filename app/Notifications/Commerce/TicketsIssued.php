<?php

namespace App\Notifications\Commerce;

use App\Models\Order;
use App\Notifications\TroofeNotification;
use Illuminate\Notifications\Messages\MailMessage;

class TicketsIssued extends TroofeNotification
{
    public function __construct(public Order $order) {}

    public function category(): string { return 'commandes'; }

    public function title(): string { return trans_choice('{1} Votre billet pour :event|[2,*] Vos :count billets pour :event', $this->order->tickets->count(), ['count' => $this->order->tickets->count(), 'event' => $this->order->event->tr('title')]); }

    public function message(): string { return __(':date — :venue, :city. Présentez le QR code à l\'entrée.', ['date' => date_human($this->order->event->starts_at, 'dddd D MMMM YYYY, HH:mm'), 'venue' => $this->order->event->venue, 'city' => $this->order->event->city]); }

    public function url(): string { return route('account.tickets'); }

    public function icon(): string { return 'bi-ticket-perforated'; }

    public function toMail(object $notifiable): MailMessage
    {
        $mail = $this->mailBase($notifiable)->line($this->message());
        foreach ($this->order->tickets as $t) {
            $mail->line('• '.$t->type->name.' — '.$t->holder_name.' — '.__('code').' '.strtoupper(substr($t->code, 0, 8)));
        }

        return $mail->action(__('Afficher mes billets'), $this->url())
            ->line(__('Chaque billet est nominatif et ne peut être scanné qu\'une seule fois.'));
    }
}

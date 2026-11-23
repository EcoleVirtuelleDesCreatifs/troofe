<?php

namespace App\Notifications\Commerce;

use App\Models\Event;
use App\Notifications\TroofeNotification;

class EventReminder extends TroofeNotification
{
    public function __construct(public Event $event) {}

    public function category(): string { return 'commandes'; }

    public function title(): string { return __('C\'est demain : :event', ['event' => $this->event->tr('title')]); }

    public function message(): string { return __('Rendez-vous :date à :venue (:city). Pensez à votre billet sur téléphone.', ['date' => date_human($this->event->starts_at, 'dddd D MMMM, HH:mm'), 'venue' => $this->event->venue, 'city' => $this->event->city]); }

    public function url(): string { return route('account.tickets'); }

    public function icon(): string { return 'bi-calendar-event'; }

    protected function actionLabel(): string { return __('Afficher mes billets'); }
}

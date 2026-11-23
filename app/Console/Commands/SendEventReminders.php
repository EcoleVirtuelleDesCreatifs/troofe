<?php

namespace App\Console\Commands;

use App\Models\Event;
use App\Models\User;
use App\Notifications\Commerce\EventReminder;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendEventReminders extends Command
{
    protected $signature = 'troofe:event-reminders';

    protected $description = 'Rappel la veille aux détenteurs de billets';

    public function handle(): int
    {
        $events = Event::published()->whereNull('reminder_sent_at')->whereBetween('starts_at', [now()->addHours(12), now()->addHours(36)])->get();
        foreach ($events as $event) {
            $holders = User::whereIn('id', $event->tickets()->select('tickets.user_id'))->get();
            Notification::send($holders, new EventReminder($event));
            $event->update(['reminder_sent_at' => now()]);
            $this->line("{$event->title} : {$holders->count()} rappel(s)");
        }

        return self::SUCCESS;
    }
}

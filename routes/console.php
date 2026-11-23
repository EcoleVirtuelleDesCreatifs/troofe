<?php

use Illuminate\Support\Facades\Schedule;

/*
 * Une seule tâche cron suffit sur l'hébergement (toutes les minutes) :
 *   * * * * * cd /chemin/vers/troofe && php artisan schedule:run >> /dev/null 2>&1
 */

// Envoi des e-mails en file d'attente (hébergement mutualisé : pas de démon permanent)
Schedule::command('queue:work --stop-when-empty --max-time=55 --tries=3')->everyMinute()->withoutOverlapping();

Schedule::command('troofe:subscriptions')->dailyAt('07:00');
Schedule::command('troofe:event-reminders')->hourly();
Schedule::command('troofe:campaigns')->dailyAt('06:30');
Schedule::command('troofe:cleanup-orders')->everyThirtyMinutes();
Schedule::command('troofe:newsletter --frequency=weekly')->weeklyOn(4, '09:00');
Schedule::command('troofe:newsletter --frequency=monthly')->monthlyOn(1, '09:30');
Schedule::command('queue:prune-failed --hours=168')->daily();
Schedule::command('model:prune')->daily();

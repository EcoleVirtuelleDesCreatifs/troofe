<?php

namespace App\Console\Commands;

use App\Models\Subscription;
use App\Notifications\Billing\SubscriptionExpired;
use App\Notifications\Billing\SubscriptionPastDue;
use App\Notifications\Billing\SubscriptionRenewalReminder;
use App\Services\ListingQuota;
use Illuminate\Console\Command;

/** Rappels J-7/J-3/J-1, passage en retard, expiration après délai de grâce */
class ProcessSubscriptions extends Command
{
    protected $signature = 'troofe:subscriptions';

    protected $description = 'Rappels d\'échéance, retards et expirations des abonnements pro';

    public function handle(ListingQuota $quota): int
    {
        $reminders = config('troofe.renewal_reminders');
        $grace = (int) config('troofe.grace_days');
        $stats = ['rappels' => 0, 'retard' => 0, 'expires' => 0];

        // 1. Rappels avant échéance (abonnements reconduits uniquement)
        Subscription::with('plan', 'user')->where('status', 'active')->where('auto_renew', true)
            ->whereBetween('ends_at', [now(), now()->addDays(max($reminders) + 1)])->each(function (Subscription $s) use ($reminders, &$stats) {
                $days = $s->daysLeft();
                $sent = $s->reminders_sent ?? [];
                foreach ($reminders as $r) {
                    if ($days <= $r && ! in_array($r, $sent, true)) {
                        $s->user->notify(new SubscriptionRenewalReminder($s, max(1, $days)));
                        $s->update(['reminders_sent' => [...$sent, ...array_filter($reminders, fn ($x) => $x >= $days)]]);
                        $stats['rappels']++;
                        break;
                    }
                }
            });

        // 2. Échéance dépassée : délai de grâce
        Subscription::with('plan', 'user')->where('status', 'active')->where('ends_at', '<', now())->each(function (Subscription $s) use ($grace, &$stats) {
            $s->update(['status' => 'past_due']);
            $s->user->notify(new SubscriptionPastDue($s, $grace));
            $stats['retard']++;
        });

        // 3. Expiration : fin du délai de grâce, ou résiliation arrivée à terme
        Subscription::with('plan', 'user')->where(fn ($q) => $q->where('status', 'past_due')->where('ends_at', '<', now()->subDays($grace))
            ->orWhere(fn ($w) => $w->where('status', 'cancelled')->where('ends_at', '<', now())))
            ->each(function (Subscription $s) use ($quota, &$stats) {
                $s->update(['status' => 'expired', 'auto_renew' => false]);
                $quota->enforce($s->user->fresh());
                $s->user->notify(new SubscriptionExpired($s));
                $stats['expires']++;
            });

        // 4. Souscriptions jamais payées depuis 7 jours : nettoyage
        Subscription::where('status', 'pending')->where('created_at', '<', now()->subDays(7))->delete();

        $this->info(sprintf('Rappels : %d · En retard : %d · Expirés : %d', ...array_values($stats)));

        return self::SUCCESS;
    }
}

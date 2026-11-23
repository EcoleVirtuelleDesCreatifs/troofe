<?php

namespace App\Console\Commands;

use App\Models\Order;
use App\Models\Payment;
use App\Services\OrderService;
use App\Services\PaymentService;
use Illuminate\Console\Command;

/** Commandes non payées après 2 h : vérification finale puis annulation (stock libéré) */
class CleanupPendingOrders extends Command
{
    protected $signature = 'troofe:cleanup-orders';

    protected $description = 'Annule les commandes impayées et libère les billets réservés';

    public function handle(OrderService $orders, PaymentService $payments): int
    {
        Payment::where('status', 'pending')->where('created_at', '<', now()->subHours(2))->each(function (Payment $p) use ($payments) {
            try {
                $payments->sync($p);
            } catch (\Throwable) {
            }
            if ($p->fresh()->status === 'pending') {
                $p->update(['status' => 'failed']);
            }
        });

        $n = 0;
        Order::where('status', 'en_attente')->where('created_at', '<', now()->subHours(2))->each(function (Order $o) use ($orders, &$n) {
            $o->type === 'billets' ? $orders->releaseStock($o) : $o->update(['status' => 'annulee']);
            $n++;
        });
        $this->info("$n commande(s) annulée(s)");

        return self::SUCCESS;
    }
}

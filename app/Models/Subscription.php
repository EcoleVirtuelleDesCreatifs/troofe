<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Subscription extends Model
{
    public const STATUSES = [
        'pending' => 'En attente de paiement',
        'active' => 'Actif',
        'past_due' => 'Échéance dépassée',
        'cancelled' => 'Résilié (actif jusqu\'à échéance)',
        'expired' => 'Expiré',
    ];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'auto_renew' => 'boolean',
            'reminders_sent' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function amount(): int
    {
        return $this->plan->price($this->billing_cycle);
    }

    public function cycleLabel(): string
    {
        return $this->billing_cycle === 'yearly' ? 'annuel' : 'mensuel';
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function daysLeft(): int
    {
        return $this->ends_at ? (int) max(0, now()->startOfDay()->diffInDays($this->ends_at->copy()->startOfDay(), false)) : 0;
    }

    /** Active (ou prolonge) l'abonnement d'une période */
    public function activateForPeriod(): void
    {
        $from = $this->ends_at && $this->ends_at->isFuture() ? $this->ends_at : now();
        $this->update([
            'status' => 'active',
            'starts_at' => $this->starts_at ?? now(),
            'ends_at' => $this->billing_cycle === 'yearly' ? $from->copy()->addYear() : $from->copy()->addMonth(),
            'reminders_sent' => [],
            'cancelled_at' => null,
        ]);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Payment extends Model
{
    public const STATUSES = ['pending' => 'En attente', 'paid' => 'Payé', 'failed' => 'Échoué', 'refunded' => 'Remboursé'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['provider_payload' => 'array', 'paid_at' => 'datetime'];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payable(): MorphTo
    {
        return $this->morphTo();
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function assignInvoiceNumber(): void
    {
        if (! $this->invoice_number) {
            $year = now()->format('Y');
            $count = static::whereNotNull('invoice_number')->where('invoice_number', 'like', "TRF-$year-%")->count() + 1;
            $this->update(['invoice_number' => sprintf('TRF-%s-%05d', $year, $count)]);
        }
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class AdCampaign extends Model
{
    public const STATUSES = [
        'en_attente' => 'En attente de validation',
        'a_payer' => 'Validée, à payer',
        'active' => 'Active',
        'terminee' => 'Terminée',
        'refusee' => 'Refusée',
        'suspendue' => 'Suspendue',
    ];

    protected $guarded = ['id', 'impressions', 'clicks'];

    protected function casts(): array
    {
        return ['starts_on' => 'date', 'ends_on' => 'date', 'ending_notice_sent_at' => 'datetime'];
    }

    public function scopeLive(Builder $q): Builder
    {
        return $q->where('status', 'active')->whereDate('starts_on', '<=', today())->whereDate('ends_on', '>=', today());
    }

    public function slot(): BelongsTo
    {
        return $this->belongsTo(AdSlot::class, 'ad_slot_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function days(): int
    {
        return $this->starts_on->diffInDays($this->ends_on) + 1;
    }

    public function ctr(): float
    {
        return $this->impressions ? round($this->clicks / $this->impressions * 100, 2) : 0;
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }
}

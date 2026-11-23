<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Booking extends Model
{
    public const STATUSES = [
        'nouvelle' => 'Nouvelle',
        'devis_envoye' => 'Devis envoyé',
        'confirmee' => 'Confirmée',
        'realisee' => 'Réalisée',
        'refusee' => 'Refusée',
        'annulee' => 'Annulée',
    ];

    public const KINDS = ['prestation' => 'Prestation', 'commande' => 'Commande', 'visite' => 'Visite d\'atelier'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['event_date' => 'date', 'history' => 'array'];
    }

    protected static function booted(): void
    {
        static::creating(function (Booking $b) {
            $b->reference ??= 'RSV-'.strtoupper(Str::random(6));
            $b->history ??= [['status' => $b->status ?? 'nouvelle', 'at' => now()->toIso8601String(), 'by' => optional($b->user)->name]];
        });
    }

    public function talent(): BelongsTo
    {
        return $this->belongsTo(Talent::class)->withTrashed();
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function offer(): BelongsTo
    {
        return $this->belongsTo(TalentOffer::class, 'talent_offer_id');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function kindLabel(): string
    {
        return self::KINDS[$this->kind] ?? $this->kind;
    }

    public function isOpen(): bool
    {
        return in_array($this->status, ['nouvelle', 'devis_envoye'], true);
    }

    public function transition(string $status, ?User $by = null, array $attrs = []): void
    {
        $history = $this->history ?? [];
        $history[] = ['status' => $status, 'at' => now()->toIso8601String(), 'by' => $by?->name ?? 'Système'];
        $this->fill($attrs + ['status' => $status, 'history' => $history])->save();
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Support\Str;

class Order extends Model
{
    public const STATUSES = [
        'en_attente' => 'En attente de paiement',
        'payee' => 'Payée',
        'confirmee' => 'Confirmée',
        'en_preparation' => 'En préparation',
        'prete' => 'Prête',
        'livree' => 'Livrée',
        'terminee' => 'Terminée',
        'annulee' => 'Annulée',
    ];

    /** Étapes que le restaurateur peut appliquer, dans l'ordre */
    public const KITCHEN_FLOW = ['payee' => 'confirmee', 'confirmee' => 'en_preparation', 'en_preparation' => 'prete', 'prete' => 'terminee'];

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['paid_at' => 'datetime', 'meta' => 'array'];
    }

    protected static function booted(): void
    {
        static::creating(fn (Order $o) => $o->reference ??= ($o->type === 'billets' ? 'BIL-' : 'CMD-').strtoupper(Str::random(7)));
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class)->withTrashed();
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class)->withTrashed();
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function payments(): MorphMany
    {
        return $this->morphMany(Payment::class, 'payable');
    }

    public function statusLabel(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    public function title(): string
    {
        return $this->type === 'billets' ? 'Billets — '.($this->event?->title ?? 'événement') : 'Commande — '.($this->restaurant?->name ?? 'restaurant');
    }

    /** Propriétaire à prévenir (restaurateur ou organisateur) */
    public function merchant(): ?User
    {
        return $this->type === 'billets' ? $this->event?->organizer : $this->restaurant?->owner;
    }
}

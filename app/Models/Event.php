<?php

namespace App\Models;

use App\Models\Concerns\Engageable;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\Moderated;
use Illuminate\Database\Eloquent\Builder;
use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use Engageable, HasSlug, Moderated, SoftDeletes, Translatable;

    protected string $slugSource = 'title';

    protected $guarded = ['id', 'views'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'reminder_sent_at' => 'datetime',
            'is_featured' => 'boolean',
        ];
    }

    public function scopeUpcoming(Builder $q): Builder
    {
        return $q->where(fn ($w) => $w->where('ends_at', '>=', now())->orWhere(fn ($x) => $x->whereNull('ends_at')->where('starts_at', '>=', now()->startOfDay())));
    }

    public function organizer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'organizer_id');
    }

    public function restaurant(): BelongsTo
    {
        return $this->belongsTo(Restaurant::class);
    }

    public function ticketTypes(): HasMany
    {
        return $this->hasMany(TicketType::class)->orderBy('price');
    }

    public function tickets(): HasManyThrough
    {
        return $this->hasManyThrough(Ticket::class, TicketType::class);
    }

    public function performers(): BelongsToMany
    {
        return $this->belongsToMany(Talent::class);
    }

    public function isFree(): bool
    {
        return $this->ticketTypes->max('price') === 0;
    }

    public function minPrice(): ?int
    {
        return $this->ticketTypes->min('price');
    }

    public function remaining(): int
    {
        return (int) $this->ticketTypes->sum(fn ($t) => max(0, $t->quantity - $t->sold));
    }

    public function coverUrl(): string
    {
        return media_url($this->cover, 'evenement', $this->slug);
    }
}

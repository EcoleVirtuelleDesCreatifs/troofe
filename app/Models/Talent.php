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
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Collection;

/** Groupe de danse, artiste (prestation artistique) ou artisan */
class Talent extends Model
{
    use Engageable, HasSlug, Moderated, SoftDeletes, Translatable;

    public const TYPES = [
        'troupe' => ['label' => 'Groupe de danse', 'plural' => 'Groupes de danse', 'route' => 'danse', 'category' => 'Style', 'icon' => 'bi-people', 'unit' => 'groupe',
            'lead' => 'Troupes traditionnelles, compagnies contemporaines et crews urbains : la danse qui fera votre événement, réservée en quelques minutes.'],
        'artiste' => ['label' => 'Prestation artistique', 'plural' => 'Prestations artistiques', 'route' => 'prestations', 'category' => 'Discipline', 'icon' => 'bi-music-note-beamed', 'unit' => 'artiste',
            'lead' => 'Griots, musiciens, DJ, conteurs, peintres en direct : des artistes à réserver pour vos mariages, dîners et événements d\'entreprise.'],
        'artisan' => ['label' => 'Artisan', 'plural' => 'Artisans', 'route' => 'artisans', 'category' => 'Métier', 'icon' => 'bi-hammer', 'unit' => 'atelier',
            'lead' => 'Tisserands, potières, sculpteurs, vanniers : commandez directement aux ateliers, équipez votre restaurant ou visitez-les.'],
    ];

    public const VISUALS = [
        'danse' => 'Danse', 'kora' => 'Kora', 'balafon' => 'Balafon', 'djembe' => 'Percussions', 'dj' => 'DJ', 'jazz' => 'Orchestre',
        'conte' => 'Conte', 'peinture' => 'Peinture', 'mc' => 'Micro / MC', 'tissage' => 'Tissage', 'poterie' => 'Poterie',
        'sculpture' => 'Sculpture', 'vannerie' => 'Vannerie', 'bijoux' => 'Bijouterie', 'calebasse' => 'Calebasses', 'maroquinerie' => 'Cuir',
    ];

    protected $guarded = ['id', 'views'];

    protected function casts(): array
    {
        return [
            'tags' => 'array', 'materials' => 'array', 'repertoire' => 'array', 'languages' => 'array',
            'audiences' => 'array', 'zones' => 'array', 'gallery' => 'array',
            'is_verified' => 'boolean', 'is_featured' => 'boolean',
        ];
    }

    public function scopeOfType(Builder $q, string $type): Builder
    {
        return $q->where('type', $type);
    }

    public function meta(string $key): string
    {
        return self::TYPES[$this->type][$key] ?? '';
    }

    public static function typeFromRoute(string $route): ?string
    {
        foreach (self::TYPES as $k => $t) {
            if ($t['route'] === $route) {
                return $k;
            }
        }

        return null;
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function offers(): HasMany
    {
        return $this->hasMany(TalentOffer::class)->orderBy('position')->orderBy('price');
    }

    public function unavailabilities(): HasMany
    {
        return $this->hasMany(TalentUnavailability::class);
    }

    public function bookings(): HasMany
    {
        return $this->hasMany(Booking::class);
    }

    public function events(): BelongsToMany
    {
        return $this->belongsToMany(Event::class);
    }

    public function restaurants(): BelongsToMany
    {
        return $this->belongsToMany(Restaurant::class);
    }

    public function dishes(): BelongsToMany
    {
        return $this->belongsToMany(Dish::class);
    }

    public function isCraft(): bool
    {
        return $this->type === 'artisan';
    }

    public function minPrice(): ?int
    {
        return $this->offers->min('price');
    }

    public function earliestDate(): string
    {
        return now()->addDays($this->min_notice_days)->toDateString();
    }

    /** Dates indisponibles : blocages manuels et prestations confirmées */
    public function busyDates(): Collection
    {
        return $this->unavailabilities()->pluck('date')->map(fn ($d) => substr((string) $d, 0, 10))
            ->merge($this->bookings()->whereIn('status', ['confirmee', 'realisee'])->whereNotNull('event_date')->pluck('event_date')->map(fn ($d) => substr((string) $d, 0, 10)))
            ->unique()->values();
    }

    public function isAvailableOn(string $date): bool
    {
        return $date >= $this->earliestDate() && ! $this->busyDates()->contains($date);
    }

    public function url(): string
    {
        return route('culture.show', [$this->meta('route'), $this]);
    }

    public function coverUrl(string $salt = ''): string
    {
        return media_url($this->cover, $this->visual ?: ($this->type === 'troupe' ? 'danse' : 'jazz'), $this->slug.$salt);
    }

    public function whatsappUrl(): ?string
    {
        return $this->whatsapp ? 'https://wa.me/'.preg_replace('/\D/', '', $this->whatsapp).'?text='.rawurlencode("Bonjour, je vous contacte depuis Trôofê au sujet de « {$this->name} ».") : null;
    }
}

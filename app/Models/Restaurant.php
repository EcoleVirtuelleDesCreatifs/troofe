<?php

namespace App\Models;

use App\Models\Concerns\Engageable;
use App\Models\Concerns\HasSlug;
use App\Models\Concerns\Moderated;
use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Restaurant extends Model
{
    use Engageable, HasSlug, Moderated, SoftDeletes, Translatable;

    protected $guarded = ['id', 'views'];

    protected function casts(): array
    {
        return [
            'opening_hours' => 'array',
            'accepts_orders' => 'boolean',
            'delivery' => 'boolean',
            'is_verified' => 'boolean',
            'is_featured' => 'boolean',
        ];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function menuItems(): HasMany
    {
        return $this->hasMany(MenuItem::class)->orderBy('category')->orderBy('position');
    }

    public function dishes(): BelongsToMany
    {
        return $this->belongsToMany(Dish::class);
    }

    public function events(): HasMany
    {
        return $this->hasMany(Event::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function craftsmen(): BelongsToMany
    {
        return $this->belongsToMany(Talent::class);
    }

    public function priceLabel(): string
    {
        return str_repeat('F', max(1, (int) $this->price_level));
    }

    public function coverUrl(): string
    {
        return media_url($this->cover, 'restaurant', $this->slug);
    }

    public function isOpenNow(): bool
    {
        $day = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'][(int) now()->format('N') - 1];
        $h = ($this->opening_hours ?? [])[$day] ?? null;
        if (! $h || ! preg_match('/(\d{1,2}):(\d{2})\s*[–-]\s*(\d{1,2}):(\d{2})/', $h, $m)) {
            return false;
        }
        $cur = now()->format('H:i');
        $open = sprintf('%02d:%02d', $m[1], $m[2]);
        $close = sprintf('%02d:%02d', $m[3], $m[4]);

        return $close <= $open ? ($cur >= $open || $cur <= $close) : ($cur >= $open && $cur <= $close);
    }

    public function whatsappUrl(): ?string
    {
        return $this->whatsapp ? 'https://wa.me/'.preg_replace('/\D/', '', $this->whatsapp).'?text='.rawurlencode("Bonjour, je vous contacte depuis Trôofê au sujet de « {$this->name} ».") : null;
    }
}

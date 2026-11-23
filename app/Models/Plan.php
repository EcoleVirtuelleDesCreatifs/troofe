<?php

namespace App\Models;

use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plan extends Model
{
    use Translatable;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'features' => 'array',
            'features_en' => 'array',
            'can_feature' => 'boolean',
            'has_stats' => 'boolean',
            'priority_support' => 'boolean',
            'is_active' => 'boolean',
        ];
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public static function free(): self
    {
        return static::firstWhere('slug', 'decouverte') ?? new static([
            'slug' => 'decouverte', 'name' => 'Découverte', 'price_monthly' => 0, 'price_yearly' => 0, 'max_listings' => 1,
        ]);
    }

    public function isFree(): bool
    {
        return (int) $this->price_monthly === 0;
    }

    public function price(string $cycle): int
    {
        return $cycle === 'yearly' ? (int) $this->price_yearly : (int) $this->price_monthly;
    }

    public function commissionRate(): float
    {
        return $this->commission_rate ?? config('troofe.commission_rate');
    }
}

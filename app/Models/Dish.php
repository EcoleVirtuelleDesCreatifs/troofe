<?php

namespace App\Models;

use App\Models\Concerns\HasSlug;
use App\Models\Concerns\Moderated;
use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Dish extends Model
{
    use HasSlug, Moderated, Translatable;

    protected $guarded = ['id', 'views'];

    protected function casts(): array
    {
        return ['ingredients' => 'array'];
    }

    public function restaurants(): BelongsToMany
    {
        return $this->belongsToMany(Restaurant::class);
    }

    public function craftsmen(): BelongsToMany
    {
        return $this->belongsToMany(Talent::class);
    }

    public function imageUrl(): string
    {
        return media_url($this->image, 'plat', $this->slug);
    }
}

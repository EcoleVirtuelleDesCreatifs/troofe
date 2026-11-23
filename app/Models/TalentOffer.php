<?php

namespace App\Models;

use App\Models\Concerns\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TalentOffer extends Model
{
    use Translatable;

    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_custom' => 'boolean'];
    }

    public function talent(): BelongsTo
    {
        return $this->belongsTo(Talent::class);
    }
}

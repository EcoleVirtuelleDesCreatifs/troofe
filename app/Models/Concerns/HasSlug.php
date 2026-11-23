<?php

namespace App\Models\Concerns;

use Illuminate\Support\Str;

trait HasSlug
{
    protected static function bootHasSlug(): void
    {
        static::saving(function ($model) {
            $source = $model->{$model->slugSource ?? 'name'};
            if (! $model->slug || $model->isDirty($model->slugSource ?? 'name') && ! $model->exists) {
                $base = Str::slug($source) ?: Str::random(8);
                $slug = $base;
                $i = 2;
                while (static::withoutGlobalScopes()->where('slug', $slug)->where('id', '!=', $model->id ?? 0)->exists()) {
                    $slug = $base.'-'.$i++;
                }
                $model->slug = $slug;
            }
        });
    }

    public function getRouteKeyName(): string
    {
        return 'slug';
    }
}

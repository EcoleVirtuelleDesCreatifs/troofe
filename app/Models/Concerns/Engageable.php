<?php

namespace App\Models\Concerns;

use App\Models\Favorite;
use App\Models\Follow;
use App\Models\Review;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;

/** Avis, favoris et abonnements (suivi) */
trait Engageable
{
    public function reviews(): MorphMany
    {
        return $this->morphMany(Review::class, 'reviewable');
    }

    public function publishedReviews(): MorphMany
    {
        return $this->reviews()->where('status', 'published')->latest();
    }

    public function favorites(): MorphMany
    {
        return $this->morphMany(Favorite::class, 'favoritable');
    }

    public function follows(): MorphMany
    {
        return $this->morphMany(Follow::class, 'followable');
    }

    public function ratingAverage(): float
    {
        return round((float) $this->publishedReviews()->avg('rating'), 1);
    }

    public function ratingCount(): int
    {
        return $this->publishedReviews()->count();
    }

    public function isFavoritedBy(?User $user): bool
    {
        return $user ? $this->favorites()->where('user_id', $user->id)->exists() : false;
    }

    public function isFollowedBy(?User $user): bool
    {
        return $user ? $this->follows()->where('user_id', $user->id)->exists() : false;
    }

    /** Abonnés à notifier (utilisateurs actifs) */
    public function followers()
    {
        return User::query()->where('is_active', true)
            ->whereIn('id', $this->follows()->select('user_id'));
    }
}

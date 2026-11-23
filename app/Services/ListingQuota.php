<?php

namespace App\Services;

use App\Models\Event;
use App\Models\Restaurant;
use App\Models\Talent;
use App\Models\User;

/**
 * Applique la limite de fiches de la formule : à l'expiration, les fiches
 * au-delà de la limite sont masquées (jamais supprimées) ; au
 * renouvellement, elles sont republiées automatiquement.
 */
class ListingQuota
{
    public const NOTE = 'quota-abonnement';

    public function enforce(User $user): int
    {
        $max = $user->currentPlan()->max_listings;
        $listings = collect()
            ->merge(Restaurant::where('owner_id', $user->id)->published()->get())
            ->merge(Talent::where('owner_id', $user->id)->published()->get())
            ->merge(Event::where('organizer_id', $user->id)->published()->upcoming()->get())
            ->sortBy('created_at')->values();

        $hidden = 0;
        foreach ($listings->slice($max) as $listing) {
            $listing->update(['status' => 'suspended', 'moderation_note' => self::NOTE]);
            $hidden++;
        }

        return $hidden;
    }

    public function restore(User $user): void
    {
        $max = $user->currentPlan()->max_listings;
        $published = $user->listingsCount();
        foreach ([Restaurant::where('owner_id', $user->id), Talent::where('owner_id', $user->id), Event::where('organizer_id', $user->id)] as $q) {
            foreach ($q->where('status', 'suspended')->where('moderation_note', self::NOTE)->orderBy('created_at')->get() as $listing) {
                if ($published >= $max) {
                    return;
                }
                $listing->update(['status' => 'published', 'moderation_note' => null]);
                $published++;
            }
        }
    }
}

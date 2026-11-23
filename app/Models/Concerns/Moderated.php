<?php

namespace App\Models\Concerns;

use Illuminate\Database\Eloquent\Builder;

/** Fiches soumises à validation : draft, pending, published, rejected, suspended */
trait Moderated
{
    public function scopePublished(Builder $q): Builder
    {
        return $q->where($this->getTable().'.status', 'published');
    }

    public function isPublished(): bool
    {
        return $this->status === 'published';
    }

    public static function statusOptions(): array
    {
        return [
            'draft' => 'Brouillon',
            'pending' => 'En attente de validation',
            'published' => 'Publié',
            'rejected' => 'Refusé',
            'suspended' => 'Suspendu',
        ];
    }
}

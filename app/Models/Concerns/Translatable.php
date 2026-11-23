<?php

namespace App\Models\Concerns;

/**
 * Contenu bilingue : le champ français est la référence, le champ « _en »
 * est facultatif. tr('description') renvoie la version anglaise si la
 * langue active est l'anglais et qu'elle est renseignée, sinon le français.
 */
trait Translatable
{
    public function tr(string $field): mixed
    {
        if (app()->getLocale() === 'en') {
            $en = $this->getAttribute($field.'_en');
            if (filled($en)) {
                return $en;
            }
        }

        return $this->getAttribute($field);
    }
}

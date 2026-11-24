<?php

use Illuminate\Support\Facades\Storage;

if (! function_exists('money')) {
    /** 12500 → « 12 500 FCFA » */
    function money(?int $amount): string
    {
        return number_format((int) $amount, 0, ',', "\u{202F}").' FCFA';
    }
}

if (! function_exists('media_url')) {
    /** Fichier téléversé, URL externe ou illustration générée par défaut */
    function media_url(?string $path, string $visual, string $seed): string
    {
        if ($path) {
            return str_starts_with($path, 'http') ? $path : Storage::disk('public')->url($path);
        }

        return \App\Support\PhotoLibrary::url($visual, $seed);
    }
}

if (! function_exists('locale_url')) {
    /** URL de la page courante dans l'autre langue */
    function locale_url(string $locale, ?array $query = null): string
    {
        $route = request()->route();
        $name = preg_replace('/^legacy\./', '', $route?->getName() ?? '');
        $target = app('router')->getRoutes()->getByName($name);
        $query ??= request()->query();
        unset($query['lang']);

        if ($target && str_starts_with($target->uri(), '{locale}')) {
            return route($name, array_merge($query, $route->parameters(), ['locale' => $locale]));
        }

        return url()->current().'?'.http_build_query(array_merge($query, ['lang' => $locale]));
    }
}

if (! function_exists('status_pill')) {
    function status_pill(string $status, ?string $label = null): string
    {
        $tone = match ($status) {
            'published', 'active', 'paid', 'payee', 'confirmee', 'realisee', 'terminee', 'livree', 'prete' => 'ok',
            'pending', 'en_attente', 'nouvelle', 'a_payer', 'past_due', 'en_preparation' => 'warn',
            'devis_envoye', 'cancelled', 'draft' => 'info',
            default => 'bad',
        };

        return '<span class="pill '.$tone.'">'.e(__($label ?? $status)).'</span>';
    }
}

if (! function_exists('date_fr')) {
    /** Date localisée lisible : « samedi 12 décembre 2026 » / « Saturday 12 December 2026 » */
    function date_human($date, string $format = 'dddd D MMMM YYYY'): string
    {
        return $date ? \Illuminate\Support\Carbon::parse($date)->locale(app()->getLocale())->isoFormat($format) : '';
    }
}

if (! function_exists('seo')) {
    /** Métadonnées SEO de la page courante */
    function seo(): \App\Support\Seo
    {
        return app(\App\Support\Seo::class);
    }
}

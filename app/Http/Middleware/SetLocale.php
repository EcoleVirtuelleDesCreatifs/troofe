<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Langue : ?lang=fr|en > session > profil > navigateur > français.
 */
class SetLocale
{
    public const LOCALES = ['fr' => 'Français', 'en' => 'English'];

    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->query('lang');

        if ($locale && array_key_exists($locale, self::LOCALES)) {
            $request->session()->put('locale', $locale);
            if ($request->user() && $request->user()->locale !== $locale) {
                $request->user()->forceFill(['locale' => $locale])->save();
            }
        } else {
            $locale = $request->session()->get('locale')
                ?? $request->user()?->locale
                ?? $request->getPreferredLanguage(array_keys(self::LOCALES))
                ?? 'fr';
        }

        app()->setLocale(array_key_exists($locale, self::LOCALES) ? $locale : 'fr');
        \Illuminate\Support\Carbon::setLocale(app()->getLocale());

        return $next($request);
    }
}

<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Langue publique déterminée par /fr ou /en ; anciennes URL redirigées, français par défaut.
 */
class SetLocale
{
    public const LOCALES = ['fr' => 'Français', 'en' => 'English'];

    public function handle(Request $request, Closure $next): Response
    {
        $queryLocale = $request->query('lang');
        $locale = $request->route('locale') ?? (is_string($queryLocale) && isset(self::LOCALES[$queryLocale]) ? $queryLocale : 'fr');
        app()->setLocale($locale);
        \Illuminate\Support\Carbon::setLocale($locale);
        \Illuminate\Support\Facades\URL::defaults(['locale' => $locale]);

        if ($request->routeIs('legacy.*')) {
            return redirect()->to(locale_url($locale), 301);
        }
        if ($request->route('locale') && $request->query->has('lang')) {
            return redirect()->to($request->fullUrlWithoutQuery('lang'), 301);
        }
        $request->route()?->forgetParameter('locale');

        $response = $next($request);
        $response->headers->set('Content-Language', $locale);

        return $response;
    }
}

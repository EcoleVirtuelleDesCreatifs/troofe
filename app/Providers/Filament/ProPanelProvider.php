<?php

namespace App\Providers\Filament;

use App\Http\Middleware\SetLocale;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class ProPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('pro')
            ->path('pro')
            ->login()
            ->brandName('Trôofê')
            ->brandLogo(asset('assets/img/logo/logo-horizontal.svg'))
            ->darkModeBrandLogo(asset('assets/img/logo/logo-horizontal-negatif.svg'))
            ->brandLogoHeight('2.4rem')
            ->favicon(asset('assets/img/logo/favicon.svg'))
            ->font('Figtree')
            ->colors(['primary' => Color::hex('#5A1E0E'), 'warning' => Color::hex('#B5802A'), 'gray' => Color::Stone])
            ->navigationGroups([0])
            ->discoverResources(in: app_path('Filament/Pro/Resources'), for: 'App\\Filament\\Pro\\Resources')
            ->pages([Pages\Dashboard::class])
            ->widgets([\App\Filament\Pro\Widgets\ProOverview::class])
            ->userMenuItems([
                MenuItem::make()->label(fn () => __('Voir le site'))->url('/')->icon('heroicon-o-globe-alt'),
                MenuItem::make()->label(fn () => __('Mon compte'))->url(fn () => route('account.dashboard'))->icon('heroicon-o-user'),
                MenuItem::make()->label(fn () => app()->getLocale() === 'fr' ? 'English' : 'Français')->url(fn () => url('/pro?lang='.(app()->getLocale() === 'fr' ? 'en' : 'fr')))->icon('heroicon-o-language'),
            ])
            ->sidebarCollapsibleOnDesktop()
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                SetLocale::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([Authenticate::class]);
    }
}

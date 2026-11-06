<?php

namespace App\Providers\Filament;

use App\Filament\NyleoAvatarProvider;
use App\Support\Brand;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\View\PanelsRenderHook;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->login()
            ->passwordReset()
            ->profile(isSimple: false)
            ->brandName('Nyleo')
            // Charte Nyleo (voir App\Support\Brand) : mêmes couleurs, polices et logo que le site.
            ->brandLogo(fn () => view('filament.brand'))
            ->brandLogoHeight('2.75rem')
            ->favicon(asset('favicon.svg'))
            ->colors(Brand::filamentColors())
            ->font('Atkinson Hyperlegible', provider: LocalFontProvider::class)
            ->darkMode(false)
            ->defaultAvatarProvider(NyleoAvatarProvider::class)
            ->renderHook(PanelsRenderHook::STYLES_AFTER, fn () => view('filament.styles'))
            ->renderHook(PanelsRenderHook::SIMPLE_LAYOUT_START, fn () => view('filament.simple-band'))
            ->renderHook(PanelsRenderHook::SIMPLE_LAYOUT_END, fn () => view('filament.simple-footer'))
            ->databaseNotifications()
            ->databaseNotificationsPolling('60s')
            ->globalSearch()
            ->navigationGroups([
                NavigationGroup::make('Activité'),
                NavigationGroup::make('Facturation'),
                NavigationGroup::make('Réglages'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}

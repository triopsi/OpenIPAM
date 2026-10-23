<?php

namespace App\Providers\Filament;

use App\Filament\Auth\FirstUserRegister;
use App\Filament\Auth\Login;
use App\Filament\Auth\Profile;
use App\Http\Middleware\EmailTwoFactorMiddleware;
use App\Http\Middleware\SetLocaleMiddleware;
use Filament\Actions\Action;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\View\PanelsRenderHook;
use Illuminate\Contracts\View\View;
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
            ->login(Login::class)
            ->registration(FirstUserRegister::class)
            ->profile(Profile::class, isSimple: true)
            ->brandName('OpenIPAM')
            ->brandLogo(fn () => view('filament.admin.logo'))
            ->brandLogoHeight('auto')
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->globalSearchFieldKeyBindingSuffix()
            ->favicon(asset('images/favicon.png'))
            ->colors([
                'primary' => Color::Blue,
                'danger' => Color::Red,
                'gray' => Color::Zinc,
                'info' => Color::Sky,
                'success' => Color::Green,
                'warning' => Color::Orange,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->navigationGroups([
                NavigationGroup::make(fn (): string => __('common.navigation.ipam')),
                NavigationGroup::make(fn (): string => __('common.navigation.settings')),
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                SetLocaleMiddleware::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
                EmailTwoFactorMiddleware::class,
            ])
            ->sidebarCollapsibleOnDesktop()
            ->databaseNotifications()
            ->databaseNotificationsPolling('30s')
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->renderHook(PanelsRenderHook::FOOTER, fn (): View => view('filament.version-footer'))
            ->userMenuItems([
                'profile' => fn (Action $action): Action => $action
                    ->label(fn (): string => __('common.navigation.profile'))
                    ->url(fn (): string => Profile::getUrl())
                    ->icon(fn (): View => view('filament.profile-menu-avatar')),
            ])
            ->renderHook(
                PanelsRenderHook::USER_MENU_BEFORE,
                fn () => view('filament.language-switcher')
            );
    }
}

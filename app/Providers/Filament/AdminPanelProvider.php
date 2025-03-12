<?php

namespace App\Providers\Filament;

use App\Services\ColorTheme;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Navigation\NavigationItem;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\Widgets;
use Hasnayeen\Themes\Http\Middleware\SetTheme;
use Hasnayeen\Themes\ThemesPlugin;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Vite;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Kenepa\Banner\BannerPlugin;
use SolutionForest\FilamentSimpleLightBox\SimpleLightBoxPlugin;


class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('')
            ->login()
            ->passwordReset()
            ->emailVerification()
            ->colors([
                'primary' => ColorTheme::getRandomFontTheme(), //initial text color
                'secondary' => Color::Slate,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([Widgets\AccountWidget::class, Widgets\FilamentInfoWidget::class])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
                SetTheme::class
            ])
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Operational Data')
                    ->icon(fn() => isMenuTop() || isSimpleSidebar() ? 'heroicon-o-rocket-launch' : ''),

                NavigationGroup::make()
                    ->label('Master Data')
                    ->icon(fn() => isMenuTop() || isSimpleSidebar() ? 'heroicon-c-square-3-stack-3d' : ''),

                NavigationGroup::make()
                    ->label('Core Data')
                    ->icon(fn() => isMenuTop() || isSimpleSidebar() ? 'heroicon-s-cpu-chip' : '')
            ])
            ->navigationItems([
                NavigationItem::make('About us')
                    ->label('Case Summary')
                    ->url(fn() => route('case-summary'), shouldOpenInNewTab: true)
                    ->badge("+ AI", 'success')
//                    ->visible(fn() => auth()->check() && (isUserAdmin() || isUserManager()))
                    ->icon('heroicon-c-magnifying-glass'),
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->databaseNotifications()
            ->databaseNotificationsPolling('10s')
            ->maxContentWidth(MaxWidth::Full)
            ->spa()
            ->brandName('BMS')
            ->brandLogo(Vite::asset('resources/images/logos/bms-new-logo.png'))
            ->brandLogoHeight('6rem')
            ->favicon(Vite::asset('resources/images/logos/bms-fav-icon.png'))
            ->font(
                'Roboto',
                url: Vite::asset('resources/css/main-fonts.css'),
                provider: LocalFontProvider::class)
            ->plugins([
                SimpleLightBoxPlugin::make(),
                ThemesPlugin::make(),
                BannerPlugin::make()
                    ->navigationGroup('Core Data')
                    ->navigationLabel('Banners')
                    ->bannerManagerAccessPermission('banner-manager')
            ])
            ->sidebarCollapsibleOnDesktop()
            ->globalSearchKeyBindings(['command+k', 'ctrl+k'])
            ->globalSearchDebounce('750ms')
            ->breadcrumbs()
            ->userMenuItems([
                MenuItem::make()
                    ->label(fn() => auth()->check() && isMenuTop() ? 'Side Menu' : 'Top Menu')
                    ->url('/menu-design-toggle')
                    ->icon(fn() => auth()->check() && isMenuTop() ? 'heroicon-o-arrow-left-circle' : 'heroicon-o-arrow-up-circle'),

                MenuItem::make()
                    ->label('Menu Items')
                    ->label(fn() => auth()->check() && !isSimpleSidebar() ? 'Hide SubMenu' : 'Show SubMenu')
                    ->url('/sidebar-items-toggle')
                    ->icon(fn() => auth()->check() && isSimpleSidebar() ? 'heroicon-o-eye' : 'heroicon-m-eye-slash'),
                MenuItem::make()
                    ->label(fn() => auth()->check() && !isModernDesign() ? 'Modern Table' : 'Classic Table')
                    ->url('/table-design-toggle')
                    ->icon(fn() => auth()->check() && !isModernDesign() ? 'heroicon-o-device-tablet' : 'heroicon-s-table-cells'),
                MenuItem::make()
                    ->label(fn() => auth()->check() && !isFilterSelected() ? 'Show Filters' : 'Hide Filters')
                    ->url('/filter-design-toggle')
                    ->icon(fn() => auth()->check() && !isFilterSelected() ? 'heroicon-o-funnel' : 'heroicon-c-arrow-path-rounded-square'),
                MenuItem::make()
                    ->label(fn() => auth()->check() && !isColorSelected() ? 'Enable Shades' : 'Disable Shades')
                    ->url('/shade-design-toggle')
                    ->icon(fn() => auth()->check() && !isColorSelected() ? 'heroicon-m-eye-dropper' : 'heroicon-c-arrow-path-rounded-square'),
            ])
            ->sidebarWidth('18rem')
            ->unsavedChangesAlerts()
            ->viteTheme('resources/css/filament/admin/theme.css')
            ->topNavigation(fn() => auth()->check() && isMenuTop());
    }
}

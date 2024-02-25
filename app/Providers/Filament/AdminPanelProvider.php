<?php

namespace App\Providers\Filament;

use App\Services\AvatarMaker;
use App\Services\ColorTheme;
use App\Services\FullScreenPlugin;
use Filament\FontProviders\LocalFontProvider;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\MenuItem;
use Filament\Navigation\NavigationGroup;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Support\Enums\MaxWidth;
use Filament\Support\Facades\FilamentView;
use Filament\Widgets;
use Hasnayeen\Themes\ThemesPlugin;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\AuthenticateSession;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Vite;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use SolutionForest\FilamentSimpleLightBox\SimpleLightBoxPlugin;
use \Hasnayeen\Themes\Http\Middleware\SetTheme;


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
                'gray' => Color::Gray, // bg color
//                'primary' => ColorTheme::getRandomFontTheme(), //initial text color
                'secondary' => Color::Slate, //secondary text color
                'danger' => Color::Rose,
                'info' => Color::Blue,
                'primary' => Color::Indigo,
                'success' => Color::Emerald,
                'warning' => Color::Orange,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Pages\Dashboard::class,
            ])
//            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
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
                    ->label('Operational Data'),
                NavigationGroup::make()
                    ->label('Master Data'),
                NavigationGroup::make()
                    ->label('Core Data')
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->databaseNotifications()
            ->maxContentWidth(MaxWidth::Full)
            ->spa()
            ->brandName('BMS')
            ->brandLogo(Vite::asset('resources/images/bms-main-logo.png'))
            ->brandLogoHeight('3rem')
            ->favicon(Vite::asset('resources/images/bms-fav-icon.png'))
            ->font(
                'Roboto',
                url: Vite::asset('resources/css/main-fonts.css'),
                provider: LocalFontProvider::class)
            ->plugins([
                SimpleLightBoxPlugin::make(),
                ThemesPlugin::make(),

            ])
            ->sidebarCollapsibleOnDesktop()
            ->breadcrumbs()
            ->userMenuItems([
                MenuItem::make()
                    ->label('Table Design')
                    ->url('/table-design-toggle')
                    ->icon('heroicon-s-table-cells'),
                MenuItem::make()
                    ->label('Chat')
//                    ->url(route('test'))
                    ->icon('heroicon-o-chat-bubble-left-right'),
            ])
            ->viteTheme('resources/css/filament/admin/theme.css');
    }


}

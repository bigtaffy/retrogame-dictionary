<?php

namespace App\Providers\Filament;

use App\Filament\Pages\CoverageMatrixPage;
use App\Filament\Pages\DataSourcesPage;
use App\Filament\Widgets\GameStatsOverview;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Enums\ThemeMode;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
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
            ->brandName('RGD 後台')
            // 強制鎖 dark mode（不跟系統 light/dark 切換，整個後台只有 dark 主題）
            ->darkMode(true, isForced: true)
            ->defaultThemeMode(ThemeMode::Dark)
            ->colors([
                'primary' => [
                    50 => '#fff0f8', 100 => '#ffd6ec', 200 => '#ffaad8',
                    300 => '#ff7ec3', 400 => '#ff5fb6', 500 => '#ff3ec8',
                    600 => '#e62fb1', 700 => '#b8228a', 800 => '#821961',
                    900 => '#4f0e3a', 950 => '#2a0620',
                ],
                'info' => [
                    50 => '#ecfaff', 100 => '#cdf1ff', 200 => '#9ee5ff',
                    300 => '#6ad7ff', 400 => '#4dd6ff', 500 => '#28b8e8',
                    600 => '#1592c2', 700 => '#126f93', 800 => '#0e4f6a',
                    900 => '#0a3548', 950 => '#06222f',
                ],
                'success' => Color::Emerald,
                'warning' => Color::Amber,
                'danger' => Color::Rose,
                'gray' => [
                    50 => '#f5f3fb', 100 => '#ece6f7', 200 => '#d8cdec',
                    300 => '#c3b5dc', 400 => '#a99cca', 500 => '#8678ad',
                    600 => '#5e527d', 700 => '#3d3458', 800 => '#1d162e',
                    900 => '#0b0420', 950 => '#060216',
                ],
            ])
            ->viteTheme('resources/css/filament/admin/theme.css')
            // Sidebar 群組順序。label 必須跟各 Resource/Page 的 $navigationGroup 一字不差。
            // Filament 4 規則：群組與其下成員不可「同時」帶 icon，否則 throw。
            // 我們的 Resource/Page 都自帶 icon → 群組這層留純文字 label。
            ->navigationGroups([
                NavigationGroup::make()->label('審核 Moderation'),
                NavigationGroup::make()->label('目錄 Catalog'),
                NavigationGroup::make()->label('使用者 Users'),
                NavigationGroup::make()->label('營運監控 Ops'),
                NavigationGroup::make()->label('系統 System'),
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->pages([
                Dashboard::class,
                CoverageMatrixPage::class,
                DataSourcesPage::class,
            ])
            ->widgets([
                GameStatsOverview::class,
                AccountWidget::class,
            ])
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

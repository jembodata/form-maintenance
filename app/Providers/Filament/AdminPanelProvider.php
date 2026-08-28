<?php

namespace App\Providers\Filament;

use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;
use Hasnayeen\Themes\ThemesPlugin;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('app')
            ->login()
            // ->databaseNotifications()
            ->topNavigation()
            ->breadcrumbs(true)
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverClusters(
                in: app_path('Filament/Clusters'),
                for: 'App\\Filament\\Clusters'
            )
            ->pages([])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->widgets([
                // Widgets\AccountWidget::class,
                // Widgets\FilamentInfoWidget::class,
            ])
            ->userMenuItems([
                \Filament\Navigation\MenuItem::make()
                    ->label('Shield')
                    ->icon('heroicon-o-shield-check')
                    ->url(function (): string {
                        $user = \Filament\Facades\Filament::auth()->user();

                        if ($user?->can('view_any_role')) {
                            return \App\Filament\Resources\RoleResource::getUrl('index');
                        }

                        return \App\Filament\Resources\UserResource::getUrl('index');
                    })
                    ->visible(function (): bool {
                        $user = \Filament\Facades\Filament::auth()->user();

                        return $user !== null
                            && (
                                $user->can('view_any_role')
                                || $user->can('view_any_user')
                            );
                    }),
            ])
            ->plugins([
                \BezhanSalleh\FilamentShield\FilamentShieldPlugin::make(),
                ThemesPlugin::make()
                    ->canViewThemesPage(function (): bool {
                        $user = \Filament\Facades\Filament::auth()->user();

                        if (! $user) {
                            return false;
                        }

                        return $user->hasRole('super_admin')
                            || $user->can('page_Themes');
                    }),
                // ThemesPlugin::make()
                //     ->registerTheme([
                //         \Hasnayeen\Themes\Themes\Nord::class,
                //     ],
                //     override: true,
                // ),
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
                \Hasnayeen\Themes\Http\Middleware\SetTheme::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }
}

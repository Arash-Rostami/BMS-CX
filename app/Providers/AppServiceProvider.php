<?php

namespace App\Providers;

use App\Models\Attachment;
use App\Models\User;
use App\Observers\AttachmentObserver;
use App\Policies\PaymentRequestPolicy;
use App\Providers\Filament\AdminPanelProvider;
use App\Services\IconMaker;
use Filament\Facades\Filament;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Field;
use Filament\Panel;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View;


class AppServiceProvider extends ServiceProvider
{


    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [

    ];


    /**
     * Register any application services.
     */
    public function register(): void
    {

    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //added js files
        FilamentAsset::register([
            Js::make('fullscreen', __DIR__ . '/../../resources/js/fullscreen.js'),
            Js::make('lightBox', 'https://cdn.jsdelivr.net/npm/fslightbox@3.4.1/index.min.js'),
            Js::make('lightBoxInit', __DIR__ . '/../../resources/js/lightBoxInit.js'),
            Js::make('connectionStatus', __DIR__ . '/../../resources/js/connectionStatus.js'),
            Js::make('sortable-js', __DIR__ . '/../../resources/js/sort.js'),
            Js::make('tweaks', __DIR__ . '/../../resources/js/tweaks.js'),
        ]);


        //added tools
        Field::macro("tooltip", function (string $tooltip) {
            return $this->hintAction(
                Action::make('help')
                    ->icon('heroicon-o-information-circle')
                    ->extraAttributes(["class" => "text-gray-500 cursor-help"])
                    ->label("")
                    ->tooltip($tooltip)
            );
        });

        // added error messages
        Component::configureUsing(function ($component) {
            if (method_exists($component, 'validationMessages')) {
                $component->validationMessages([
                    'required' => '🚫 This field is required.',
                ]);
            }
        });


        // added components
        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_START,
            fn(): View => view('components.overlay'),
        );


        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn(): View => view('components.events'),
        );


        Attachment::observe(AttachmentObserver::class);

//        Notifications::alignment(Alignment::Start);
//        Notifications::verticalAlignment(VerticalAlignment::End);


        Gate::define('banner-manager', function (User $user) {
            return $user->isAdmin() || $user->isManager();
        });
    }
}

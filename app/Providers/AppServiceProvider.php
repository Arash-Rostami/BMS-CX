<?php

namespace App\Providers;

use App\Models\Attachment;
use App\Models\Payment;
use App\Models\PaymentRequest;
use App\Observers\AttachmentObserver;
use App\Observers\PaymentObserver;
use App\Policies\PaymentRequestPolicy;
use App\Services\IconMaker;
use Filament\Forms\Components\Component;
use Filament\Support\Assets\Js;
use Filament\Support\Enums\Alignment;
use Filament\Support\Enums\VerticalAlignment;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentView;
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
        FilamentAsset::register([
            Js::make('fullscreen', __DIR__ . '/../../resources/js/fullscreen.js'),
            Js::make('lightBox', 'https://cdn.jsdelivr.net/npm/fslightbox@3.4.1/index.min.js'),
            Js::make('lightBoxInit', __DIR__ . '/../../resources/js/lightBoxInit.js'),
            Js::make('connectionStatus', __DIR__ . '/../../resources/js/connectionStatus.js'),

        ]);

        Component::configureUsing(function ($component) {
            if (method_exists($component, 'validationMessages')) {
                $component->validationMessages([
                    'required' => '🚫 This field is required.',
                ]);
            }
        });

        Attachment::observe(AttachmentObserver::class);

//        Notifications::alignment(Alignment::Start);
//        Notifications::verticalAlignment(VerticalAlignment::End);
    }
}

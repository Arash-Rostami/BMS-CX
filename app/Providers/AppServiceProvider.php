<?php

namespace App\Providers;

use App\Models\Allocation;
use App\Models\Attachment;
use App\Models\Beneficiary;
use App\Models\Buyer;
use App\Models\Category;
use App\Models\Contractor;
use App\Models\Grade;
use App\Models\Name;
use App\Models\Order;
use App\Models\Payment;
use App\Models\PaymentRequest;
use App\Models\PortOfDelivery;
use App\Models\Product;
use App\Models\ProformaInvoice;
use App\Models\PurchaseStatus;
use App\Models\Supplier;
use App\Models\User;
use App\Observers\AllocationObserver;
use App\Observers\AttachmentObserver;
use App\Observers\BeneficiaryObserver;
use App\Observers\BuyerObserver;
use App\Observers\CategoryObserver;
use App\Observers\ContractorObserver;
use App\Observers\GradeObserver;
use App\Observers\NameObserver;
use App\Observers\OrderObserver;
use App\Observers\PaymentObserver;
use App\Observers\PaymentRequestObserver;
use App\Observers\PortOfDeliveryObserver;
use App\Observers\ProductObserver;
use App\Observers\ProformaInvoiceObserver;
use App\Observers\PurchaseStatusObserver;
use App\Observers\SupplierObserver;
use App\Observers\UserObserver;
use Filament\Forms\Components\Actions\Action;
use Filament\Forms\Components\Component;
use Filament\Forms\Components\Field;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
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
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->registerFilamentAssets();
        $this->registerFilamentMacros();
        $this->configureFilamentComponents();
        $this->registerFilamentRenderHooks();
        $this->registerModelObservers();
        $this->registerGates();
        // $this->registerInactiveFeatures();
    }


    public function register(): void
    {
    }

    private function configureFilamentComponents(): void
    {
        Component::configureUsing(function ($component) {
            if (method_exists($component, 'validationMessages')) {
                $component->validationMessages([
                    'required' => '🚫 This field is required.',
                ]);
            }
        });
    }

    private function registerFilamentAssets(): void
    {
        FilamentAsset::register([
            Js::make('fullscreen', __DIR__ . '/../../resources/js/fullscreen.js'),
            Js::make('lightBoxLocal', __DIR__ . '/../../resources/js/lightBoxLocal.js'),
            Js::make('lightBoxInit', __DIR__ . '/../../resources/js/lightBoxInit.js'),
            Js::make('connectionStatus', __DIR__ . '/../../resources/js/connectionStatus.js'),
            Js::make('sortable-js', __DIR__ . '/../../resources/js/sort.js'),
            Js::make('tweaks', __DIR__ . '/../../resources/js/tweaks.js'),
        ]);
    }

    private function registerFilamentMacros(): void
    {
        Field::macro("tooltip", function (string $tooltip) {
            return $this->hintAction(
                Action::make('help')
                    ->icon('heroicon-o-information-circle')
                    ->extraAttributes(["class" => "text-gray-500 cursor-help"])
                    ->label("")
                    ->tooltip($tooltip)
            );
        });
    }

    private function registerFilamentRenderHooks(): void
    {
        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_START,
            fn(): View => view('components.overlay'),
        );

        FilamentView::registerRenderHook(
            PanelsRenderHook::BODY_END,
            fn(): View => view('components.events'),
        );
    }

    private function registerGates(): void
    {
        Gate::define('banner-manager', fn(User $user) => $user->hasRole('admin') || $user->hasRole('manager'));
    }

    private function registerInactiveFeatures(): void
    {
        // Notifications::alignment(Alignment::Start);
        // Notifications::verticalAlignment(VerticalAlignment::End);
    }

    private function registerModelObservers(): void
    {
        // Transactional Data Observers
        Attachment::observe(AttachmentObserver::class);
        Order::observe(OrderObserver::class);
        Payment::observe(PaymentObserver::class);
        PaymentRequest::observe(PaymentRequestObserver::class);
        ProformaInvoice::observe(ProformaInvoiceObserver::class);

        // Master Data Observers
        Allocation::observe(AllocationObserver::class);
        Beneficiary::observe(BeneficiaryObserver::class);
        Buyer::observe(BuyerObserver::class);
        Category::observe(CategoryObserver::class);
        Contractor::observe(ContractorObserver::class);
        Grade::observe(GradeObserver::class);
        Name::observe(NameObserver::class);
        PortOfDelivery::observe(PortOfDeliveryObserver::class);
        Product::observe(ProductObserver::class);
        PurchaseStatus::observe(PurchaseStatusObserver::class);
        Supplier::observe(SupplierObserver::class);
        User::observe(UserObserver::class);
    }
}

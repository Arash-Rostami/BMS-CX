<?php

namespace App\Providers;

// use Illuminate\Support\Facades\Gate;
use App\Models\Buyer;
use App\Models\Category;
use App\Models\Contractor;
use App\Models\DeliveryTerm;
use App\Models\Grade;
use App\Models\Notification;
use App\Models\Order;
use App\Models\OrderRequest;
use App\Models\Packaging;
use App\Models\Payment;
use App\Models\PaymentRequest;
use App\Models\Permission;
use App\Models\PortOfDelivery;
use App\Models\Product;
use App\Models\PurchaseStatus;
use App\Models\ShippingLine;
use App\Models\Supplier;
use App\Models\Tag;
use App\Policies\BuyerPolicy;
use App\Policies\CategoryPolicy;
use App\Policies\ContractorPolicy;
use App\Policies\DeliveryTermPolicy;
use App\Policies\GradePolicy;
use App\Policies\NotificationPolicy;
use App\Policies\OrderPolicy;
use App\Policies\OrderRequestPolicy;
use App\Policies\PackagingPolicy;
use App\Policies\PaymentPolicy;
use App\Policies\PaymentRequestPolicy;
use App\Policies\PermissionPolicy;
use App\Policies\PortOfDeliveryPolicy;
use App\Policies\ProductPolicy;
use App\Policies\PurchaseStatusPolicy;
use App\Policies\ShippingLinePolicy;
use App\Policies\SupplierPolicy;
use App\Policies\TagPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The model to policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Buyer::class => BuyerPolicy::class,
        Category::class => CategoryPolicy::class,
        Contractor::class => ContractorPolicy::class,
        DeliveryTerm::class => DeliveryTermPolicy::class,
        Grade::class => GradePolicy::class,
        Notification::class => NotificationPolicy::class,
        Order::class => OrderPolicy::class,
        OrderRequest::class => OrderRequestPolicy::class,
        Packaging::class => PackagingPolicy::class,
        Payment::class => PaymentPolicy::class,
        PaymentRequest::class => PaymentRequestPolicy::class,
        Permission::class => PermissionPolicy::class,
        PortOfDelivery::class => PortOfDeliveryPolicy::class,
        Product::class => ProductPolicy::class,
        PurchaseStatus::class => PurchaseStatusPolicy::class,
        ShippingLine::class => ShippingLinePolicy::class,
        Supplier::class => SupplierPolicy::class,
        Tag::class => TagPolicy::class
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        //
    }
}

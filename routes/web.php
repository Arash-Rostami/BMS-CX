<?php

//use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AttachmentController;
use App\Http\Controllers\CostCalculationController;
use App\Http\Controllers\ManagerialDashboard;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\UserController;
use App\Livewire\CaseSummary\TotalSummary;
use App\Models\ProformaInvoice;
use App\Services\SupplierSummaryService;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;


Route::get('/clear', function () {
    if (!Auth::check()) {
        abort(403, 'Unauthorized');
    }

    Artisan::call('schedule:clear-cache');
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    Artisan::call('optimize:clear');
    Artisan::call('filament:clear-cached-components');

    return "All caches including Filament caches have been cleared successfully!";
});

Route::get('/cache', function () {
    if (!Auth::check()) {
        abort(403, 'Unauthorized');
    }

    Artisan::call('filament:cache-components');

    return "All caches including Filament caches have been rebuilt successfully!";
});



Route::middleware(['web', 'custom_auth'])->group(function () {
    Route::get('/auth-checker/attachment/{path}', [AttachmentController::class, 'serve'])
        ->where('path', '.*')
        ->name('attachments.secure');

    Route::get('/dashboard', [ManagerialDashboard::class, 'index'])
        ->name('ManagerialDashboard');

    Route::get('/table-design-toggle', [UserController::class, 'toggleTableDesign'])
        ->name('table.design.toggle');

    Route::get('/menu-design-toggle', [UserController::class, 'toggleMenuDesign'])
        ->name('menu.design.toggle');

    Route::get('/filter-design-toggle', [UserController::class, 'toggleFilterDesign'])
        ->name('filter.design.toggle');

    Route::get('/sidebar-items-toggle', [UserController::class, 'toggleSidebarItems'])
        ->name('sidebar-items-toggle');

    Route::get('/shade-design-toggle', [UserController::class, 'toggleShadeDesign'])
        ->name('shade-design-toggle');

    Route::get('/case-summary', [TotalSummary::class, 'index'])
        ->name('case-summary');

    Route::get('/cost-calculation', [CostCalculationController::class, 'index'])
        ->name('cost-calculation');

});


Route::get('/test-summary', function (SupplierSummaryService $service) {
    ProformaInvoice::select('id')
        ->chunk(50, function ($slice) use ($service) {
            foreach ($slice as $pi) {
                $service->rebuild($pi->id);
            }
            sleep(2);
        });
});

Route::get('/quote-service/{token}', [QuoteController::class, 'authenticate'])->name('quote-service');

Route::fallback(fn() => view('errors.404'));



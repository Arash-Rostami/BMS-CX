<?php

//use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\UserController;
use App\Mail\QuoteRequestEmail;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;

use Illuminate\Support\Facades\Mail;

Route::get('/test-email', function () {
//     Mail::to('arashrostami@time-gr.com')->send(new QuoteRequestEmail(
//        'Quote Request: New Details', // Dynamic subject
//        'Hello **User**, here are your quote details.'
//    ));
//    Mail::raw('This is a test email.', function ($message) {
//        $message->to('arashrostami@time-gr.com')->subject('Test Email');
//    });
//
//    return 'Test email sent!';
});

Route::get('/clear', function() {
    Artisan::call('optimize:clear');
    return redirect()->back();
});

Route::get('/table-design-toggle', [UserController::class, 'toggleTableDesign'])
    ->name('table.design.toggle');

Route::get('/quote-service/{token}', [QuoteController::class, 'authenticate'])->name('quote-service');

Route::fallback(fn() => view('errors.404'));



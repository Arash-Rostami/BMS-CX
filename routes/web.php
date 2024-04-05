<?php

//use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\QuoteController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;


Route::get('/table-design-toggle', [UserController::class, 'toggleTableDesign'])
    ->name('table.design.toggle');

Route::get('/quote-service/{token}', [QuoteController::class, 'authenticate']);


Route::fallback(fn() => view('errors.404'));



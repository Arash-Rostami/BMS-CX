<?php

use App\Http\Controllers\BotController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});


Route::middleware(['web', 'admin'])
    ->prefix('bot')
    ->group(function () {
        Route::get('/{name}/create', [BotController::class, 'createTable']);
        Route::get('/{name}/insert', [BotController::class, 'insertTable']);
        Route::get('/{name}/fetch', [BotController::class, 'fetchTable']);
        Route::get('/{name}/delete', [BotController::class, 'deleteTable']);

        Route::get('/create-bulk', [BotController::class, 'createTables']);
        Route::get('/insert-bulk', [BotController::class, 'insertTables']);
        Route::get('/delete-bulk', [BotController::class, 'deleteTables']);
    });

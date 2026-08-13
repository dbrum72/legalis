<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\ClientController;
use App\Http\Controllers\MaritalStatusController;

Route::prefix('auth')->group(function () {
    Route::post('/login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function () {
        Route::get('/me', [AuthController::class, 'me']);
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::post('/refresh', [AuthController::class, 'refresh']);
    });
});

Route::middleware('auth:api')
    ->prefix('clients')
    ->group(function () {
        Route::get('/', [ClientController::class, 'index'])
            ->middleware('can:clients.view');

        Route::post('/', [ClientController::class, 'store'])
            ->middleware('can:clients.create');

        Route::get('/{client}', [ClientController::class, 'show'])
            ->middleware('can:clients.view');

        Route::patch('/{client}', [ClientController::class, 'update'])
            ->middleware('can:clients.update');

        Route::delete('/{client}', [ClientController::class, 'destroy'])
            ->middleware('can:clients.delete');
    });

Route::middleware('auth:api')
    ->get(
        '/marital-statuses',
        [MaritalStatusController::class, 'index']
    );

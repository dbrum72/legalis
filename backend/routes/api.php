<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\FolderClientController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\MaritalStatusController;
use App\Http\Controllers\QualificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')
    ->group(function () {
        Route::post(
            '/login',
            [AuthController::class, 'login']
        );

        Route::middleware('auth:api')
            ->group(function () {
                Route::get(
                    '/me',
                    [AuthController::class, 'me']
                );

                Route::post(
                    '/logout',
                    [AuthController::class, 'logout']
                );

                Route::post(
                    '/refresh',
                    [AuthController::class, 'refresh']
                );
            });
    });

Route::middleware('auth:api')
    ->group(function () {
        Route::get(
            '/marital-statuses',
            [MaritalStatusController::class, 'index']
        );

        Route::get(
            '/qualifications',
            [QualificationController::class, 'index']
        );
    });

Route::middleware([
    'auth:api',
    'tenant',
])
    ->group(function () {
        Route::prefix('clients')
            ->group(function () {
                Route::get(
                    '/',
                    [ClientController::class, 'index']
                )->middleware(
                    'can:clients.view'
                );

                Route::post(
                    '/',
                    [ClientController::class, 'store']
                )->middleware(
                    'can:clients.create'
                );

                Route::get(
                    '/{client}',
                    [ClientController::class, 'show']
                )->middleware(
                    'can:clients.view'
                );

                Route::patch(
                    '/{client}',
                    [ClientController::class, 'update']
                )->middleware(
                    'can:clients.update'
                );

                Route::delete(
                    '/{client}',
                    [ClientController::class, 'destroy']
                )->middleware(
                    'can:clients.delete'
                );
            });

        Route::get(
            '/folders',
            [FolderController::class, 'index']
        )->middleware(
            'can:folders.view'
        );

        Route::post(
            '/folders',
            [FolderController::class, 'store']
        )->middleware(
            'can:folders.create'
        );

        Route::get(
            '/folders/{folder}',
            [FolderController::class, 'show']
        )->middleware(
            'can:folders.view'
        );

        Route::patch(
            '/folders/{folder}',
            [FolderController::class, 'update']
        )->middleware(
            'can:folders.update'
        );

        Route::delete(
            '/folders/{folder}',
            [FolderController::class, 'destroy']
        )->middleware(
            'can:folders.delete'
        );

        Route::post(
            '/folders/{folder}/clients',
            [
                FolderClientController::class,
                'store',
            ]
        )->middleware(
            'can:folders.update'
        );

        Route::patch(
            '/folders/{folder}/clients/{folderClient}',
            [
                FolderClientController::class,
                'update',
            ]
        )->middleware(
            'can:folders.update'
        );

        Route::delete(
            '/folders/{folder}/clients/{folderClient}',
            [
                FolderClientController::class,
                'destroy',
            ]
        )->middleware(
            'can:folders.update'
        );
    });
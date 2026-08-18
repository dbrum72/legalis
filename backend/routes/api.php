<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\FolderClientController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\FolderDeadlineController;
use App\Http\Controllers\FolderDocumentController;
use App\Http\Controllers\FolderEventController;
use App\Http\Controllers\FolderMovementController;
use App\Http\Controllers\MaritalStatusController;
use App\Http\Controllers\OrganizationInvitationController;
use App\Http\Controllers\OrganizationMemberController;
use App\Http\Controllers\OrganizationRoleController;
use App\Http\Controllers\QualificationController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')
    ->group(function () {
        Route::post(
            '/login',
            [
                AuthController::class,
                'login',
            ]
        );

        Route::middleware(
            'auth:api'
        )
            ->group(function () {
                Route::get(
                    '/me',
                    [
                        AuthController::class,
                        'me',
                    ]
                );

                Route::post(
                    '/logout',
                    [
                        AuthController::class,
                        'logout',
                    ]
                );

                Route::post(
                    '/refresh',
                    [
                        AuthController::class,
                        'refresh',
                    ]
                );
            });

        Route::middleware([
            'auth:api',
            'tenant',
        ])
            ->get(
                '/context',
                [
                    AuthController::class,
                    'context',
                ]
            );
    });

Route::get(
    '/organization-invitations/accept/{token}',
    [
        OrganizationInvitationController::class,
        'showAcceptance',
    ]
);

Route::post(
    '/organization-invitations/accept/{token}',
    [
        OrganizationInvitationController::class,
        'accept',
    ]
);

Route::middleware(
    'auth:api'
)
    ->group(function () {
        Route::get(
            '/marital-statuses',
            [
                MaritalStatusController::class,
                'index',
            ]
        );

        Route::get(
            '/qualifications',
            [
                QualificationController::class,
                'index',
            ]
        );
    });

Route::middleware([
    'auth:api',
    'tenant',
])
    ->group(function () {
        /*
        |--------------------------------------------------------------------------
        | Dashboard
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/dashboard',
            [
                DashboardController::class,
                'index',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Organization
        |--------------------------------------------------------------------------
        */

        Route::post(
            '/organization-invitations',
            [
                OrganizationInvitationController::class,
                'store',
            ]
        )->middleware(
            'can:organization-members.invite'
        );

        Route::get(
            '/organization-members',
            [
                OrganizationMemberController::class,
                'index',
            ]
        )->middleware(
            'can:organization-members.view'
        );

        Route::patch(
            '/organization-members/{user}/role',
            [
                OrganizationMemberController::class,
                'updateRole',
            ]
        )->middleware(
            'can:organization-members.update-role'
        );

        Route::patch(
            '/organization-members/{user}/status',
            [
                OrganizationMemberController::class,
                'updateStatus',
            ]
        )->middleware(
            'can:organization-members.update-status'
        );

        Route::get(
            '/organization-roles',
            [
                OrganizationRoleController::class,
                'index',
            ]
        )->middleware(
            'can:organization-members.view'
        );

        /*
        |--------------------------------------------------------------------------
        | Clients
        |--------------------------------------------------------------------------
        */

        Route::prefix('clients')
            ->group(function () {
                Route::get(
                    '/',
                    [
                        ClientController::class,
                        'index',
                    ]
                )->middleware(
                    'can:clients.view'
                );

                Route::post(
                    '/',
                    [
                        ClientController::class,
                        'store',
                    ]
                )->middleware(
                    'can:clients.create'
                );

                Route::get(
                    '/{client}',
                    [
                        ClientController::class,
                        'show',
                    ]
                )->middleware(
                    'can:clients.view'
                );

                Route::patch(
                    '/{client}',
                    [
                        ClientController::class,
                        'update',
                    ]
                )->middleware(
                    'can:clients.update'
                );

                Route::delete(
                    '/{client}',
                    [
                        ClientController::class,
                        'destroy',
                    ]
                )->middleware(
                    'can:clients.delete'
                );
            });

        /*
        |--------------------------------------------------------------------------
        | Folders
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/folders',
            [
                FolderController::class,
                'index',
            ]
        )->middleware(
            'can:folders.view'
        );

        Route::post(
            '/folders',
            [
                FolderController::class,
                'store',
            ]
        )->middleware(
            'can:folders.create'
        );

        Route::get(
            '/folders/{folder}',
            [
                FolderController::class,
                'show',
            ]
        )->middleware(
            'can:folders.view'
        );

        Route::patch(
            '/folders/{folder}',
            [
                FolderController::class,
                'update',
            ]
        )->middleware(
            'can:folders.update'
        );

        Route::delete(
            '/folders/{folder}',
            [
                FolderController::class,
                'destroy',
            ]
        )->middleware(
            'can:folders.delete'
        );

        /*
        |--------------------------------------------------------------------------
        | Folder Clients
        |--------------------------------------------------------------------------
        */

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
        )
            ->scopeBindings()
            ->middleware(
                'can:folders.update'
            );

        Route::delete(
            '/folders/{folder}/clients/{folderClient}',
            [
                FolderClientController::class,
                'destroy',
            ]
        )
            ->scopeBindings()
            ->middleware(
                'can:folders.update'
            );

        /*
        |--------------------------------------------------------------------------
        | Folder Documents
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/folders/{folder}/documents',
            [
                FolderDocumentController::class,
                'index',
            ]
        )->middleware(
            'can:folders.view'
        );

        Route::post(
            '/folders/{folder}/documents',
            [
                FolderDocumentController::class,
                'store',
            ]
        )->middleware(
            'can:folders.update'
        );

        Route::get(
            '/folders/{folder}/documents/{document}/download',
            [
                FolderDocumentController::class,
                'download',
            ]
        )
            ->scopeBindings()
            ->middleware(
                'can:folders.view'
            );

        Route::delete(
            '/folders/{folder}/documents/{document}',
            [
                FolderDocumentController::class,
                'destroy',
            ]
        )
            ->scopeBindings()
            ->middleware(
                'can:folders.update'
            );

        /*
        |--------------------------------------------------------------------------
        | Folder Movements
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/folders/{folder}/movements',
            [
                FolderMovementController::class,
                'index',
            ]
        )->middleware(
            'can:folders.view'
        );

        Route::post(
            '/folders/{folder}/movements',
            [
                FolderMovementController::class,
                'store',
            ]
        )->middleware(
            'can:folders.update'
        );

        Route::delete(
            '/folders/{folder}/movements/{movement}',
            [
                FolderMovementController::class,
                'destroy',
            ]
        )
            ->scopeBindings()
            ->middleware(
                'can:folders.update'
            );

        /*
        |--------------------------------------------------------------------------
        | Folder Deadlines
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/folders/{folder}/deadlines',
            [
                FolderDeadlineController::class,
                'index',
            ]
        )->middleware(
            'can:folders.view'
        );

        Route::post(
            '/folders/{folder}/deadlines',
            [
                FolderDeadlineController::class,
                'store',
            ]
        )->middleware(
            'can:folders.update'
        );

        Route::patch(
            '/folders/{folder}/deadlines/{deadline}/complete',
            [
                FolderDeadlineController::class,
                'complete',
            ]
        )
            ->scopeBindings()
            ->middleware(
                'can:folders.update'
            );

        Route::delete(
            '/folders/{folder}/deadlines/{deadline}',
            [
                FolderDeadlineController::class,
                'destroy',
            ]
        )
            ->scopeBindings()
            ->middleware(
                'can:folders.update'
            );

        /*
        |--------------------------------------------------------------------------
        | Folder Events
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/folders/{folder}/events',
            [
                FolderEventController::class,
                'index',
            ]
        )->middleware(
            'can:folders.view'
        );

        Route::post(
            '/folders/{folder}/events',
            [
                FolderEventController::class,
                'store',
            ]
        )->middleware(
            'can:folders.update'
        );

        Route::patch(
            '/folders/{folder}/events/{event}/complete',
            [
                FolderEventController::class,
                'complete',
            ]
        )
            ->scopeBindings()
            ->middleware(
                'can:folders.update'
            );

        Route::delete(
            '/folders/{folder}/events/{event}',
            [
                FolderEventController::class,
                'destroy',
            ]
        )
            ->scopeBindings()
            ->middleware(
                'can:folders.update'
            );
    });

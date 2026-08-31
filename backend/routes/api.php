<?php

use App\Http\Controllers\AgendaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DjenSyncController;
use App\Http\Controllers\DataJudSyncController;
use App\Http\Controllers\FolderClientController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\FolderDeadlineController;
use App\Http\Controllers\FolderDocumentController;
use App\Http\Controllers\FolderEventController;
use App\Http\Controllers\FolderMovementController;
use App\Http\Controllers\FolderTaskController;
use App\Http\Controllers\LegalPublicationController;
use App\Http\Controllers\MaritalStatusController;
use App\Http\Controllers\MonitoredBarRegistrationController;
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

        Route::post(
            '/register',
            [
                AuthController::class,
                'register',
            ],
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

        Route::post(
            '/dashboard/datajud-integrations/{integrationSyncRun}/seen',
            [DashboardController::class, 'markDataJudIntegrationSeen'],
        );

        /*
        |--------------------------------------------------------------------------
        | Agenda
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/agenda',
            [
                AgendaController::class,
                'index',
            ]
        );

        /*
        |--------------------------------------------------------------------------
        | Organization
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/organization-invitations',
            [
                OrganizationInvitationController::class,
                'index',
            ]
        )->middleware(
            'can:organization-members.invite'
        );

        Route::post(
            '/organization-invitations',
            [
                OrganizationInvitationController::class,
                'store',
            ]
        )->middleware(
            'can:organization-members.invite'
        );

        Route::post(
            '/organization-invitations/{organizationInvitation}/resend',
            [
                OrganizationInvitationController::class,
                'resend',
            ]
        )
            ->whereNumber(
                'organizationInvitation'
            )
            ->middleware(
                'can:organization-members.invite'
            );

        Route::patch(
            '/organization-invitations/{organizationInvitation}/revoke',
            [
                OrganizationInvitationController::class,
                'revoke',
            ]
        )
            ->whereNumber(
                'organizationInvitation'
            )
            ->middleware(
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
        | Publications
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/monitored-bar-registrations',
            [
                MonitoredBarRegistrationController::class,
                'index',
            ]
        )->middleware(
            'can:publications.view'
        );

        Route::post(
            '/monitored-bar-registrations',
            [
                MonitoredBarRegistrationController::class,
                'store',
            ]
        )->middleware(
            'can:publications.manage-monitoring'
        );

        Route::patch(
            '/monitored-bar-registrations/{monitoredBarRegistration}',
            [
                MonitoredBarRegistrationController::class,
                'update',
            ]
        )->middleware(
            'can:publications.manage-monitoring'
        );

        Route::post(
            '/monitored-bar-registrations/{monitoredBarRegistration}/sync',
            [
                DjenSyncController::class,
                'store',
            ]
        )->middleware(
            'can:publications.sync'
        );

        Route::get(
            '/legal-publications',
            [
                LegalPublicationController::class,
                'index',
            ]
        )->middleware(
            'can:publications.view'
        );

        Route::get(
            '/legal-publications/{legalPublication}',
            [
                LegalPublicationController::class,
                'show',
            ]
        )->middleware(
            'can:publications.view'
        );

        Route::patch(
            '/legal-publications/{legalPublication}/folder',
            [
                LegalPublicationController::class,
                'link',
            ]
        )->middleware(
            'can:publications.review'
        );

        Route::patch(
            '/legal-publications/{legalPublication}/review',
            [
                LegalPublicationController::class,
                'review',
            ]
        )->middleware(
            'can:publications.review'
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

        Route::post(
            '/folders/{folder}/datajud/sync',
            [
                DataJudSyncController::class,
                'store',
            ]
        )->middleware(
            'can:folders.update'
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

        /*
        |--------------------------------------------------------------------------
        | Folder Tasks
        |--------------------------------------------------------------------------
        */

        Route::get(
            '/folders/{folder}/tasks',
            [
                FolderTaskController::class,
                'index',
            ]
        )->middleware(
            'can:folders.view'
        );

        Route::post(
            '/folders/{folder}/tasks',
            [
                FolderTaskController::class,
                'store',
            ]
        )->middleware(
            'can:folders.update'
        );

        Route::patch(
            '/folders/{folder}/tasks/{task}/complete',
            [
                FolderTaskController::class,
                'complete',
            ]
        )
            ->scopeBindings()
            ->middleware(
                'can:folders.update'
            );

        Route::delete(
            '/folders/{folder}/tasks/{task}',
            [
                FolderTaskController::class,
                'destroy',
            ]
        )
            ->scopeBindings()
            ->middleware(
                'can:folders.update'
            );
    });

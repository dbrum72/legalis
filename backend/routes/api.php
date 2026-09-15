<?php

use App\Http\Controllers\AgendaController;
use App\Http\Controllers\CashFlowController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DataJudSyncController;
use App\Http\Controllers\DjenSyncController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\FeeAgreementController;
use App\Http\Controllers\FinancialSummaryController;
use App\Http\Controllers\FinancialReportController;
use App\Http\Controllers\FolderClientController;
use App\Http\Controllers\FolderController;
use App\Http\Controllers\FolderDeadlineController;
use App\Http\Controllers\FolderDocumentController;
use App\Http\Controllers\FolderEventController;
use App\Http\Controllers\FolderMovementController;
use App\Http\Controllers\FolderTaskController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\InvoiceReminderController;
use App\Http\Controllers\InvoiceReminderRuleController;
use App\Http\Controllers\LegalPublicationController;
use App\Http\Controllers\MaritalStatusController;
use App\Http\Controllers\MonitoredBarRegistrationController;
use App\Http\Controllers\OrganizationInvitationController;
use App\Http\Controllers\OrganizationMemberController;
use App\Http\Controllers\OrganizationRoleController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\PayableController;
use App\Http\Controllers\PayablePaymentController;
use App\Http\Controllers\PostalCodeController;
use App\Http\Controllers\QualificationController;
use App\Http\Controllers\TimeEntryController;
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
        Route::middleware('can:documents.generate')->group(function () {
            Route::get('/document-templates/fields', [\App\Http\Controllers\DocumentAutomationController::class, 'fields']);
            Route::get('/document-templates', [\App\Http\Controllers\DocumentAutomationController::class, 'index']);
            Route::post('/document-templates', [\App\Http\Controllers\DocumentAutomationController::class, 'store'])->middleware('can:folders.update');
            Route::post('/document-templates/{template}/replace', [\App\Http\Controllers\DocumentAutomationController::class, 'replace'])->middleware('can:folders.update');
            Route::delete('/document-templates/{template}', [\App\Http\Controllers\DocumentAutomationController::class, 'destroy'])->middleware('can:folders.update');
            Route::get('/document-templates/{template}/download', [\App\Http\Controllers\DocumentAutomationController::class, 'download']);
            Route::get('/folders/{folder}/document-generation/context', [\App\Http\Controllers\DocumentAutomationController::class, 'context'])->middleware('can:folders.view');
            Route::post('/folders/{folder}/document-generation/preview', [\App\Http\Controllers\DocumentAutomationController::class, 'preview'])->middleware('can:folders.view');
            Route::post('/folders/{folder}/document-generation', [\App\Http\Controllers\DocumentAutomationController::class, 'generate'])->middleware(['can:folders.view', 'can:folders.update']);
        });
        Route::get(
            '/postal-codes/{postalCode}',
            [PostalCodeController::class, 'show'],
        )->where('postalCode', '[0-9]{8}');

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

        Route::get(
            '/organization-roles/{role}',
            [OrganizationRoleController::class, 'show']
        )->middleware(
            'can:roles.view'
        );

        Route::patch(
            '/organization-roles/{role}/permissions',
            [OrganizationRoleController::class, 'updatePermissions']
        )->middleware(
            'can:roles.update'
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
        | Folder financial operations
        |--------------------------------------------------------------------------
        */

        Route::post('/folders/{folder}/billing', [InvoiceController::class, 'storeFromFolderItems'])
            ->middleware('can:finance.manage');

        Route::get('/folders/{folder}/fee-agreements', [FeeAgreementController::class, 'index'])
            ->middleware('can:finance.view');
        Route::post('/folders/{folder}/fee-agreements', [FeeAgreementController::class, 'store'])
            ->middleware('can:finance.manage');
        Route::patch('/folders/{folder}/fee-agreements/{feeAgreement}', [FeeAgreementController::class, 'update'])
            ->middleware('can:finance.manage');
        Route::delete('/folders/{folder}/fee-agreements/{feeAgreement}', [FeeAgreementController::class, 'destroy'])
            ->middleware('can:finance.manage');

        Route::get('/folders/{folder}/time-entries', [TimeEntryController::class, 'index'])
            ->middleware('can:time-entries.view');
        Route::post('/folders/{folder}/time-entries', [TimeEntryController::class, 'store'])
            ->middleware('can:time-entries.create');
        Route::patch('/folders/{folder}/time-entries/{timeEntry}', [TimeEntryController::class, 'update'])
            ->middleware('can:time-entries.update');
        Route::delete('/folders/{folder}/time-entries/{timeEntry}', [TimeEntryController::class, 'destroy'])
            ->middleware('can:time-entries.delete');

        Route::get('/folders/{folder}/expenses', [ExpenseController::class, 'index'])
            ->middleware('can:expenses.view');
        Route::post('/folders/{folder}/expenses', [ExpenseController::class, 'store'])
            ->middleware('can:expenses.create');
        Route::patch('/folders/{folder}/expenses/{expense}', [ExpenseController::class, 'update'])
            ->middleware('can:expenses.update');
        Route::delete('/folders/{folder}/expenses/{expense}', [ExpenseController::class, 'destroy'])
            ->middleware('can:expenses.delete');

        Route::get('/finance/summary', FinancialSummaryController::class)
            ->middleware('can:finance.view');
        Route::get('/finance/classifications', [\App\Http\Controllers\FinancialClassificationController::class, 'index']);
        Route::post('/finance/classifications', [\App\Http\Controllers\FinancialClassificationController::class, 'store'])
            ->middleware('can:finance.manage');
        Route::patch('/finance/classifications/{classification}', [\App\Http\Controllers\FinancialClassificationController::class, 'update'])
            ->middleware('can:finance.manage');
        Route::get('/finance/cash-flow', [CashFlowController::class, 'index'])
            ->middleware('can:finance.view');
        Route::get('/finance/cash-flow/export', [CashFlowController::class, 'export'])
            ->middleware('can:finance.view');
        Route::get('/finance/report', FinancialReportController::class)
            ->middleware('can:finance.view');
        Route::get('/finance/reconciliation', [\App\Http\Controllers\FinancialReconciliationController::class, 'index'])->middleware('can:finance.view');
        Route::put('/finance/reconciliation/{kind}/{id}', [\App\Http\Controllers\FinancialReconciliationController::class, 'check'])->whereIn('kind', ['incoming', 'outgoing'])->whereNumber('id')->middleware('can:finance.manage');
        Route::post('/finance/closing', [\App\Http\Controllers\FinancialReconciliationController::class, 'close'])->middleware('can:finance.manage');
        Route::post('/finance/closing/reopen', [\App\Http\Controllers\FinancialReconciliationController::class, 'reopen'])->middleware('can:finance.manage');
        Route::get('/payables/alerts', [\App\Http\Controllers\PayableRecurrenceController::class, 'alerts'])->middleware('can:finance.view');
        Route::get('/payable-recurrences', [\App\Http\Controllers\PayableRecurrenceController::class, 'index'])->middleware('can:finance.view');
        Route::post('/payable-recurrences', [\App\Http\Controllers\PayableRecurrenceController::class, 'store'])->middleware('can:finance.manage');
        Route::patch('/payable-recurrences/{recurrence}', [\App\Http\Controllers\PayableRecurrenceController::class, 'update'])->middleware('can:finance.manage');
        Route::get('/payable-recurrences/{recurrence}/preview', [\App\Http\Controllers\PayableRecurrenceController::class, 'preview'])->middleware('can:finance.view');
        Route::post('/payable-recurrences/{recurrence}/generate', [\App\Http\Controllers\PayableRecurrenceController::class, 'generate'])->middleware('can:finance.manage');
        Route::get('/payables', [PayableController::class, 'index'])->middleware('can:finance.view');
        Route::post('/payables', [PayableController::class, 'store'])->middleware('can:finance.manage');
        Route::patch('/payables/{payable}', [PayableController::class, 'update'])->middleware('can:finance.manage');
        Route::delete('/payables/{payable}', [PayableController::class, 'destroy'])->middleware('can:finance.manage');
        Route::post('/payables/{payable}/payments', [PayablePaymentController::class, 'store'])->middleware('can:finance.manage');
        Route::post('/payables/{payable}/cancel', [PayableController::class, 'cancel'])->middleware('can:finance.manage');
        Route::post('/payables/{payable}/payments/{payment}/cancel', [PayablePaymentController::class, 'cancel'])->middleware('can:finance.manage');
        Route::get('/finance/reminder-rules', [InvoiceReminderRuleController::class, 'index'])
            ->middleware('can:finance.view');
        Route::post('/finance/reminder-rules', [InvoiceReminderRuleController::class, 'store'])
            ->middleware('can:finance.manage');
        Route::patch('/finance/reminder-rules/{reminderRule}', [InvoiceReminderRuleController::class, 'update'])
            ->middleware('can:finance.manage');
        Route::delete('/finance/reminder-rules/{reminderRule}', [InvoiceReminderRuleController::class, 'destroy'])
            ->middleware('can:finance.manage');
        Route::get('/invoices', [InvoiceController::class, 'index'])
            ->middleware('can:finance.view');
        Route::get('/invoices/export', [InvoiceController::class, 'export'])
            ->middleware('can:finance.view');
        Route::get('/invoices/{invoice}', [InvoiceController::class, 'show'])
            ->middleware('can:finance.view');
        Route::post('/invoices', [InvoiceController::class, 'store'])
            ->middleware('can:finance.manage');
        Route::patch('/invoices/{invoice}', [InvoiceController::class, 'update'])
            ->middleware('can:finance.manage');
        Route::delete('/invoices/{invoice}', [InvoiceController::class, 'destroy'])
            ->middleware('can:finance.manage');
        Route::post('/invoices/{invoice}/payments', [PaymentController::class, 'store'])
            ->middleware('can:finance.manage');
        Route::get('/invoices/{invoice}/payments/{payment}/receipt', [PaymentController::class, 'receipt'])
            ->middleware('can:finance.view');
        Route::post('/invoices/{invoice}/payments/{payment}/receipt', [PaymentController::class, 'sendReceipt'])
            ->middleware('can:finance.manage');
        Route::post('/invoices/{invoice}/reminders', [InvoiceReminderController::class, 'store'])
            ->middleware('can:finance.manage');
        Route::post('/invoices/{invoice}/payments/{payment}/cancel', [PaymentController::class, 'cancel'])
            ->middleware('can:finance.manage');
        Route::post('/invoices/{invoice}/installments', [InvoiceController::class, 'storeInstallment'])
            ->middleware('can:finance.manage');
        Route::post('/invoices/{invoice}/cancel', [InvoiceController::class, 'cancel'])
            ->middleware('can:finance.manage');

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

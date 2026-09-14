<?php

namespace App\Console\Commands;

use App\Mail\InvoiceReminderMail;
use App\Models\InvoiceReminder;
use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use Throwable;

class SendScheduledInvoiceReminders extends Command
{
    protected $signature = 'finance:send-reminders';

    protected $description = 'Envia lembretes de cobrança agendados';

    public function handle(CurrentOrganization $currentOrganization): int
    {
        InvoiceReminder::withoutGlobalScopes()->where('status', 'scheduled')
            ->where('scheduled_at', '<=', now())->orderBy('id')->chunkById(100, function ($reminders) use ($currentOrganization): void {
                foreach ($reminders as $reminder) {
                    $reminder->load('organization:id,name', 'invoice.organization:id,name');
                    $currentOrganization->set($reminder->organization);
                    try {
                        Mail::to($reminder->recipient)->send(new InvoiceReminderMail($reminder->invoice, $reminder->subject, $reminder->message));
                        $reminder->update(['status' => 'sent', 'sent_at' => now(), 'error_message' => null]);
                    } catch (Throwable $exception) {
                        report($exception);
                        $reminder->update(['status' => 'failed', 'error_message' => mb_substr($exception->getMessage(), 0, 1000)]);
                    } finally {
                        $currentOrganization->clear();
                    }
                }
            });

        return self::SUCCESS;
    }
}

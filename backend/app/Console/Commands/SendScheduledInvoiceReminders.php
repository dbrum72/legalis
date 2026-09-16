<?php

namespace App\Console\Commands;

use App\Mail\InvoiceReminderMail;
use App\Models\InvoiceReminder;
use App\Models\Scopes\OrganizationScope;
use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Mail;
use LogicException;
use Throwable;

class SendScheduledInvoiceReminders extends Command
{
    protected $signature = 'finance:send-reminders';

    protected $description = 'Envia lembretes de cobrança agendados';

    public function handle(CurrentOrganization $currentOrganization): int
    {
        InvoiceReminder::withoutGlobalScope(OrganizationScope::class)->where('status', 'scheduled')
            ->whereHas('organization', fn ($query) => $query->where('status', 'active'))
            ->with('organization')
            ->where('scheduled_at', '<=', now())->orderBy('id')->chunkById(100, function ($reminders) use ($currentOrganization): void {
                foreach ($reminders as $reminder) {
                    $currentOrganization->run($reminder->organization, function () use ($reminder): void {
                        try {
                            // Resolve the invoice only after entering the reminder's organization.
                            $reminder->load('invoice.organization');
                            if ($reminder->invoice === null) {
                                throw new LogicException('A cobrança não pertence ao escritório do lembrete ou não está disponível.');
                            }
                            Mail::to($reminder->recipient)->send(new InvoiceReminderMail($reminder->invoice, $reminder->subject, $reminder->message));
                            $reminder->update(['status' => 'sent', 'sent_at' => now(), 'error_message' => null]);
                        } catch (Throwable $exception) {
                            report($exception);
                            $reminder->update(['status' => 'failed', 'error_message' => mb_substr($exception->getMessage(), 0, 1000)]);
                        }
                    });
                }
            });

        return self::SUCCESS;
    }
}

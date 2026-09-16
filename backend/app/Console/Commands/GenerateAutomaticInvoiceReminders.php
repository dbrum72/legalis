<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\InvoiceReminderRule;
use App\Models\Scopes\OrganizationScope;
use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Console\Command;

class GenerateAutomaticInvoiceReminders extends Command
{
    protected $signature = 'finance:generate-reminders';

    protected $description = 'Gera lembretes automáticos conforme a régua de cobrança';

    public function handle(CurrentOrganization $currentOrganization): int
    {
        InvoiceReminderRule::withoutGlobalScope(OrganizationScope::class)->where('active', true)
            ->whereHas('organization', fn ($query) => $query->where('status', 'active'))
            ->with('organization')
            ->orderBy('id')->chunkById(100, function ($rules) use ($currentOrganization): void {
                foreach ($rules as $rule) {
                    $currentOrganization->run($rule->organization, function () use ($rule): void {
                        Invoice::query()->with('client:id,name,email')->whereIn('status', ['open', 'partial'])
                            ->where('balance_cents', '>', 0)->whereNotNull('due_on')
                            ->whereDate('due_on', '<=', today()->subDays($rule->days_after_due))
                            ->whereHas('client', fn ($query) => $query->whereNotNull('email')->where('email', '!=', ''))
                            ->chunkById(100, function ($invoices) use ($rule): void {
                                foreach ($invoices as $invoice) {
                                    $key = "rule:{$rule->id}:due:{$invoice->due_on->format('Y-m-d')}";
                                    $replacements = ['{cliente}' => $invoice->client->name, '{cobranca}' => $invoice->charge_identifier, '{vencimento}' => $invoice->due_on->format('d/m/Y')];
                                    $invoice->reminders()->firstOrCreate(['automation_key' => $key], [
                                        'invoice_reminder_rule_id' => $rule->id,
                                        'recipient' => $invoice->client->email,
                                        'subject' => strtr($rule->subject, $replacements),
                                        'message' => strtr($rule->message, $replacements),
                                        'status' => 'scheduled',
                                        'scheduled_at' => now(),
                                    ]);
                                }
                            });
                    });
                }
            });

        return self::SUCCESS;
    }
}

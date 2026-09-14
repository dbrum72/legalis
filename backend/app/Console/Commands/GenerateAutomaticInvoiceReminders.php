<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Models\InvoiceReminderRule;
use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Console\Command;

class GenerateAutomaticInvoiceReminders extends Command
{
    protected $signature = 'finance:generate-reminders';

    protected $description = 'Gera lembretes automáticos conforme a régua de cobrança';

    public function handle(CurrentOrganization $currentOrganization): int
    {
        InvoiceReminderRule::withoutGlobalScopes()->where('active', true)->with('organization:id,name')
            ->orderBy('id')->chunkById(100, function ($rules) use ($currentOrganization): void {
                foreach ($rules as $rule) {
                    $currentOrganization->set($rule->organization);
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
                    $currentOrganization->clear();
                }
            });

        return self::SUCCESS;
    }
}

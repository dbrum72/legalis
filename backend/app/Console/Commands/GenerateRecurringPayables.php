<?php

namespace App\Console\Commands;

use App\Models\Organization;
use App\Models\PayableRecurrence;
use App\Services\PayableRecurrenceService;
use App\Support\Tenancy\CurrentOrganization;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Throwable;

class GenerateRecurringPayables extends Command
{
    protected $signature = 'finance:generate-payables';
    protected $description = 'Gera contas recorrentes até 30 dias à frente, sem duplicidade.';

    public function handle(PayableRecurrenceService $service, CurrentOrganization $context): int
    {
        $previous = $context->has() ? $context->get() : null;
        $created = 0;
        $failures = 0;
        try {
            foreach (Organization::where('status', 'active')->cursor() as $organization) {
                $context->set($organization);
                PayableRecurrence::where('active', true)->whereDate('next_due_on', '<=', CarbonImmutable::today('America/Sao_Paulo')->addDays(30))
                    ->chunkById(100, function ($rules) use ($service, &$created, &$failures): void {
                        foreach ($rules as $rule) {
                            try {
                                $created += $service->generate($rule, CarbonImmutable::today('America/Sao_Paulo')->addDays(30));
                            } catch (Throwable $exception) {
                                $failures++;
                                report($exception);
                                $rule->update(['last_error' => 'Geração interrompida. Confira os vínculos e classificações da recorrência; pause e crie uma nova série se necessário.']);
                            }
                        }
                    });
            }
        } finally {
            $previous ? $context->set($previous) : $context->clear();
        }
        $this->info("Contas geradas: {$created}. Recorrências com falha: {$failures}.");

        return $failures ? self::FAILURE : self::SUCCESS;
    }
}

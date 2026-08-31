<?php

namespace App\Console\Commands;

use App\Jobs\SyncDjenBarRegistration;
use App\Models\MonitoredBarRegistration;
use App\Services\Publications\ImportDjenPublications;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use LogicException;
use Throwable;

class SyncDjenPublications extends Command
{
    protected $signature =
        'djen:sync-publications
        {--from= : Data inicial no formato AAAA-MM-DD}
        {--to= : Data final no formato AAAA-MM-DD}
        {--organization= : Slug da organização}
        {--run-now : Executa no processo atual, sem enfileirar}';

    protected $description =
        'Sincroniza publicações do DJEN para as inscrições OAB monitoradas';

    public function handle(
        ImportDjenPublications $service,
    ): int {
        $timezone =
            (string) config(
                'services.djen.timezone',
                'America/Sao_Paulo',
            );

        try {
            $periodEnd =
                $this->dateOption(
                    'to',
                    CarbonImmutable::now(
                        $timezone
                    )->startOfDay(),
                    $timezone,
                );

            $periodStart =
                $this->dateOption(
                    'from',
                    $periodEnd->subDays(
                        (int) config(
                            'services.djen.lookback_days',
                            3,
                        )
                    ),
                    $timezone,
                );
        } catch (Throwable) {
            $this->error(
                'Informe as datas no formato AAAA-MM-DD.'
            );

            return self::FAILURE;
        }

        if ($periodEnd->lessThan($periodStart)) {
            $this->error(
                'A data final não pode ser anterior à data inicial.'
            );

            return self::FAILURE;
        }

        $query =
            MonitoredBarRegistration::query()
                ->with('organization')
                ->where(
                    'active',
                    true,
                )
                ->orderBy('id');

        $organizationSlug =
            trim(
                (string) $this->option(
                    'organization'
                )
            );

        if ($organizationSlug !== '') {
            $query->whereHas(
                'organization',
                fn ($organizationQuery) => $organizationQuery->where(
                    'slug',
                    $organizationSlug,
                ),
            );
        }

        $registrations =
            $query->get();

        foreach (
            $registrations as $barRegistration
        ) {
            if ($this->option('run-now')) {
                $service->handle(
                    $barRegistration,
                    $periodStart,
                    $periodEnd,
                );

                continue;
            }

            SyncDjenBarRegistration::dispatch(
                $barRegistration->id,
                $periodStart->toDateString(),
                $periodEnd->toDateString(),
            );
        }

        $this->info(
            sprintf(
                '%d inscrição(ões) OAB processada(s).',
                $registrations->count(),
            )
        );

        return self::SUCCESS;
    }

    private function dateOption(
        string $option,
        CarbonImmutable $default,
        string $timezone,
    ): CarbonImmutable {
        $value =
            trim(
                (string) $this->option(
                    $option
                )
            );

        if ($value === '') {
            return $default;
        }

        $date =
            CarbonImmutable::createFromFormat(
                '!Y-m-d',
                $value,
                $timezone,
            );

        if (
            $date === false
            || $date->toDateString() !== $value
        ) {
            throw new LogicException(
                'Data inválida.'
            );
        }

        return $date;
    }
}

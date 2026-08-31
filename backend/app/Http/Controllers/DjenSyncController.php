<?php

namespace App\Http\Controllers;

use App\Http\Requests\DjenSyncRequest;
use App\Jobs\SyncDjenBarRegistration;
use App\Models\MonitoredBarRegistration;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;

class DjenSyncController extends Controller
{
    public function store(
        DjenSyncRequest $request,
        MonitoredBarRegistration $monitoredBarRegistration,
    ): JsonResponse {
        if (! $monitoredBarRegistration->active) {
            return response()->json(
                [
                    'message' => 'Ative o monitoramento desta inscrição OAB antes de sincronizar.',
                ],
                422,
            );
        }

        $timezone =
            (string) config(
                'services.djen.timezone',
                'America/Sao_Paulo',
            );

        $periodEnd =
            $request->validated(
                'to'
            ) !== null
                ? CarbonImmutable::parse(
                    $request->validated('to'),
                    $timezone,
                )
                : CarbonImmutable::now(
                    $timezone
                )->startOfDay();

        $periodStart =
            $request->validated(
                'from'
            ) !== null
                ? CarbonImmutable::parse(
                    $request->validated('from'),
                    $timezone,
                )
                : $this->defaultPeriodStart(
                    $monitoredBarRegistration,
                    $periodEnd,
                    $timezone,
                );

        SyncDjenBarRegistration::dispatch(
            $monitoredBarRegistration->id,
            $periodStart->toDateString(),
            $periodEnd->toDateString(),
        );

        return response()->json(
            [
                'message' => 'Sincronização do DJEN enfileirada com sucesso.',

                'period_start' => $periodStart->toDateString(),

                'period_end' => $periodEnd->toDateString(),
            ],
            202,
        );
    }

    private function defaultPeriodStart(
        MonitoredBarRegistration $barRegistration,
        CarbonImmutable $periodEnd,
        string $timezone,
    ): CarbonImmutable {
        if ($barRegistration->last_synced_at !== null) {
            return CarbonImmutable::instance(
                $barRegistration->last_synced_at
            )
                ->setTimezone($timezone)
                ->startOfDay()
                ->subDay();
        }

        if ($barRegistration->monitoring_started_on !== null) {
            return CarbonImmutable::parse(
                $barRegistration
                    ->monitoring_started_on
                    ->toDateString(),
                $timezone,
            )->startOfDay();
        }

        return $periodEnd->subDays(
            (int) config(
                'services.djen.lookback_days',
                3,
            )
        );
    }
}

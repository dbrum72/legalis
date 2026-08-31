<?php

namespace App\Jobs;

use App\Models\MonitoredBarRegistration;
use App\Services\Publications\ImportDjenPublications;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SyncDjenBarRegistration implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $uniqueFor = 3600;

    public array $backoff = [
        60,
        300,
        900,
    ];

    public function __construct(
        public readonly int $barRegistrationId,
        public readonly string $periodStart,
        public readonly string $periodEnd,
    ) {}

    public function handle(
        ImportDjenPublications $service,
    ): void {
        $barRegistration =
            MonitoredBarRegistration::query()
                ->with('organization')
                ->find(
                    $this->barRegistrationId
                );

        if (
            $barRegistration === null
            || ! $barRegistration->active
        ) {
            return;
        }

        $service->handle(
            $barRegistration,
            CarbonImmutable::parse(
                $this->periodStart
            ),
            CarbonImmutable::parse(
                $this->periodEnd
            ),
        );
    }

    public function uniqueId(): string
    {
        return implode(
            ':',
            [
                $this->barRegistrationId,
                $this->periodStart,
                $this->periodEnd,
            ],
        );
    }
}

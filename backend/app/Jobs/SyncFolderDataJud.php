<?php

namespace App\Jobs;

use App\Models\Folder;
use App\Models\IntegrationSyncRun;
use App\Models\Scopes\OrganizationScope;
use App\Services\Folders\SyncFolderWithDataJud;
use App\Support\Tenancy\CurrentOrganization;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Support\Str;
use Throwable;

class SyncFolderDataJud implements ShouldBeUnique, ShouldQueue
{
    use Queueable;

    public int $tries = 4;

    public int $uniqueFor = 3600;

    public int $timeout = 90;

    public bool $requireMonitoring = true;

    public array $backoff = [
        60,
        300,
        900,
        1800,
    ];

    public function __construct(
        public readonly int $folderId,
        public readonly string $syncDate,
        bool $requireMonitoring = true,
    ) {
        $this->requireMonitoring = $requireMonitoring;
        $this->onQueue('integrations');
    }

    public function middleware(): array
    {
        return [
            (new RateLimited('datajud'))->releaseAfter(60),
        ];
    }

    public function handle(SyncFolderWithDataJud $service): void
    {
        // The queued ID is a platform lookup; all subsequent work is tenant-scoped.
        $folder = Folder::withoutGlobalScope(OrganizationScope::class)
            ->with('organization')
            ->whereHas('organization', fn ($query) => $query->where('status', 'active'))
            ->when($this->requireMonitoring, fn ($query) => $query->where('datajud_monitoring_enabled', true))
            ->find($this->folderId);

        if ($folder === null) {
            return;
        }

        app(CurrentOrganization::class)->run(
            $folder->organization,
            fn () => $this->synchronize($folder, $service),
        );
    }

    private function synchronize(Folder $folder, SyncFolderWithDataJud $service): void
    {
        $folder->forceFill([
            'datajud_last_attempt_at' => now(),
            'datajud_sync_error' => null,
        ])->save();

        $run = IntegrationSyncRun::query()->create([
            'organization_id' => $folder->organization_id,
            'folder_id' => $folder->id,
            'provider' => 'datajud',
            'status' => IntegrationSyncRun::STATUS_RUNNING,
            'period_start' => null,
            'period_end' => null,
            'started_at' => now(),
            'metadata' => [
                'sync_date' => $this->syncDate,
                'attempt' => $this->attempts(),
            ],
        ]);

        try {
            $result = $service->execute($folder);

            $folder->forceFill([
                'datajud_last_success_at' => now(),
                'datajud_next_sync_at' => $this->nextSyncAt(),
                'datajud_sync_error' => null,
            ])->save();

            $run->forceFill([
                'status' => IntegrationSyncRun::STATUS_SUCCEEDED,
                'finished_at' => now(),
                'items_seen' => (int) ($result['movements_seen'] ?? 0),
                'items_imported' => (int) ($result['movements_imported'] ?? 0),
                'metadata' => array_merge(
                    $run->metadata ?? [],
                    ['alias' => $folder->fresh()->datajud_alias],
                ),
            ])->save();
        } catch (Throwable $exception) {
            $message = Str::limit($exception->getMessage(), 2000, '');

            $folder->forceFill([
                'datajud_sync_error' => $message,
                'datajud_next_sync_at' => $this->nextSyncAt(),
            ])->save();

            $run->forceFill([
                'status' => IntegrationSyncRun::STATUS_FAILED,
                'finished_at' => now(),
                'error_message' => $message,
            ])->save();

            throw $exception;
        }
    }

    public function uniqueId(): string
    {
        return $this->folderId.':'.$this->syncDate;
    }

    private function nextSyncAt(): CarbonImmutable
    {
        $timezone = (string) config('services.datajud.timezone', 'America/Sao_Paulo');
        $time = (string) config('services.datajud.sync_time', '04:00');
        [$hour, $minute] = array_map('intval', explode(':', $time));

        return CarbonImmutable::now($timezone)
            ->addDay()
            ->startOfDay()
            ->setTime($hour, $minute);
    }
}

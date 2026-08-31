<?php

namespace App\Services\Publications;

use App\Integrations\Djen\Contracts\DjenClient;
use App\Integrations\Djen\DjenPublicationMapper;
use App\Models\Folder;
use App\Models\IntegrationSyncRun;
use App\Models\LegalPublication;
use App\Models\MonitoredBarRegistration;
use App\Support\Tenancy\CurrentOrganization;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use LogicException;
use RuntimeException;
use Throwable;

class ImportDjenPublications
{
    public function __construct(
        private readonly DjenClient $client,
        private readonly DjenPublicationMapper $mapper,
        private readonly CurrentOrganization $currentOrganization,
    ) {}

    public function handle(
        MonitoredBarRegistration $barRegistration,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
    ): IntegrationSyncRun {
        if ($periodEnd->lessThan($periodStart)) {
            throw new LogicException(
                'A data final da sincronização não pode ser anterior à data inicial.'
            );
        }

        $barRegistration->loadMissing(
            'organization'
        );

        $mustClearOrganization =
            ! $this->currentOrganization->has();

        if (
            ! $mustClearOrganization
            && $this->currentOrganization->id()
                !== (int) $barRegistration->organization_id
        ) {
            throw new LogicException(
                'A inscrição OAB não pertence à organização atual.'
            );
        }

        if ($mustClearOrganization) {
            $this->currentOrganization->set(
                $barRegistration->organization
            );
        }

        try {
            return $this->import(
                $barRegistration,
                $periodStart,
                $periodEnd,
            );
        } finally {
            if ($mustClearOrganization) {
                $this->currentOrganization->clear();
            }
        }
    }

    private function import(
        MonitoredBarRegistration $barRegistration,
        CarbonImmutable $periodStart,
        CarbonImmutable $periodEnd,
    ): IntegrationSyncRun {
        $syncRun =
            IntegrationSyncRun::query()
                ->create([
                    'monitored_bar_registration_id' => $barRegistration->id,

                    'provider' => 'djen',

                    'status' => IntegrationSyncRun::STATUS_RUNNING,

                    'period_start' => $periodStart->toDateString(),

                    'period_end' => $periodEnd->toDateString(),

                    'started_at' => now(),
                ]);

        $itemsSeen = 0;
        $itemsImported = 0;
        $itemsLinked = 0;

        try {
            $folderIdsByProcess =
                $this->folderIdsByProcess();

            $page = 1;
            $pagesProcessed = 0;
            $maxPages =
                max(
                    1,
                    (int) config(
                        'services.djen.max_pages_per_sync',
                        100,
                    ),
                );

            while ($page <= $maxPages) {
                $searchPage =
                    $this->client
                        ->searchByBarRegistration(
                            $barRegistration->bar_number,
                            $barRegistration->state,
                            $periodStart,
                            $periodEnd,
                            $page,
                        );

                $pagesProcessed++;

                foreach (
                    $searchPage->items as $payload
                ) {
                    if (! is_array($payload)) {
                        continue;
                    }

                    $itemsSeen++;

                    $result =
                        $this->persistPublication(
                            $barRegistration,
                            $payload,
                            $folderIdsByProcess,
                        );

                    $itemsImported +=
                        $result['imported'];

                    $itemsLinked +=
                        $result['linked'];
                }

                if (! $searchPage->hasMore) {
                    break;
                }

                if ($page >= $maxPages) {
                    throw new RuntimeException(
                        'A sincronização do DJEN excedeu o limite de páginas configurado. Reduza o período consultado.'
                    );
                }

                $page++;
            }

            $finishedAt =
                now();

            $barRegistration->forceFill([
                'last_synced_at' => $finishedAt,
            ])->save();

            $syncRun->forceFill([
                'status' => IntegrationSyncRun::STATUS_SUCCEEDED,

                'finished_at' => $finishedAt,

                'items_seen' => $itemsSeen,

                'items_imported' => $itemsImported,

                'items_linked' => $itemsLinked,

                'metadata' => [
                    'pages_processed' => $pagesProcessed,
                ],
            ])->save();

            return $syncRun->refresh();
        } catch (Throwable $exception) {
            $syncRun->forceFill([
                'status' => IntegrationSyncRun::STATUS_FAILED,

                'finished_at' => now(),

                'items_seen' => $itemsSeen,

                'items_imported' => $itemsImported,

                'items_linked' => $itemsLinked,

                'error_message' => mb_substr(
                    $exception->getMessage(),
                    0,
                    5000,
                ),
            ])->save();

            throw $exception;
        }
    }

    private function folderIdsByProcess(): Collection
    {
        return Folder::query()
            ->whereNotNull(
                'process_number'
            )
            ->get([
                'id',
                'process_number',
            ])
            ->mapWithKeys(
                function (Folder $folder): array {
                    $normalized =
                        $this->mapper
                            ->normalizeProcessNumber(
                                $folder->process_number
                            );

                    if ($normalized === null) {
                        return [];
                    }

                    return [
                        $normalized => $folder->id,
                    ];
                }
            );
    }

    private function persistPublication(
        MonitoredBarRegistration $barRegistration,
        array $payload,
        Collection $folderIdsByProcess,
    ): array {
        $mapped =
            $this->mapper->map(
                $payload
            );

        $publication =
            LegalPublication::query()
                ->firstOrNew([
                    'source' => $mapped['source'],

                    'external_id' => $mapped['external_id'],
                ]);

        $isNew =
            ! $publication->exists;

        $wasUnlinked =
            $publication->folder_id === null;

        $publication->fill(
            $mapped
        );

        if ($isNew) {
            $publication->review_status =
                LegalPublication::REVIEW_PENDING;

            $publication->imported_at =
                now();
        }

        if (
            $publication->folder_id === null
            && $mapped['normalized_process_number'] !== null
        ) {
            $publication->folder_id =
                $folderIdsByProcess->get(
                    $mapped['normalized_process_number']
                );
        }

        $publication->last_seen_at =
            now();

        $publication->save();

        $publication
            ->barRegistrations()
            ->syncWithoutDetaching([
                $barRegistration->id,
            ]);

        return [
            'imported' => $isNew ? 1 : 0,

            'linked' => $wasUnlinked
                && $publication->folder_id !== null
                    ? 1
                    : 0,
        ];
    }
}

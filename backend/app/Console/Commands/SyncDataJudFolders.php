<?php

namespace App\Console\Commands;

use App\Jobs\SyncFolderDataJud;
use App\Models\Folder;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;

class SyncDataJudFolders extends Command
{
    protected $signature =
        'datajud:sync-folders
        {--organization= : Slug da organização}
        {--folder= : ID de uma pasta específica}
        {--failed-only : Processa apenas pastas cuja última execução falhou}
        {--run-now : Executa no processo atual, sem enfileirar}';

    protected $description =
        'Sincroniza no DataJud as pastas com monitoramento habilitado';

    public function handle(): int
    {
        $timezone = (string) config('services.datajud.timezone', 'America/Sao_Paulo');
        $syncDate = CarbonImmutable::now($timezone)->toDateString();
        $folderId = trim((string) $this->option('folder'));

        $query = Folder::query()
            ->with('organization')
            ->where('datajud_monitoring_enabled', true)
            ->whereNotNull('process_number')
            ->orderBy('id');

        if ($folderId !== '') {
            $query->whereKey($folderId);
        } else {
            $query->where(function ($dueQuery): void {
                $dueQuery
                    ->whereNull('datajud_next_sync_at')
                    ->orWhere('datajud_next_sync_at', '<=', now());
            });
        }

        $organization = trim((string) $this->option('organization'));

        if ($organization !== '') {
            $query->whereHas(
                'organization',
                fn ($organizationQuery) => $organizationQuery->where('slug', $organization),
            );
        }

        if ($this->option('failed-only')) {
            $query->whereNotNull('datajud_sync_error');
        }

        $processed = 0;

        $query->chunkById(100, function ($folders) use ($syncDate, &$processed): void {
            foreach ($folders as $folder) {
                $job = new SyncFolderDataJud($folder->id, $syncDate);

                if ($this->option('run-now')) {
                    Bus::dispatchSync($job);
                } else {
                    SyncFolderDataJud::dispatch($folder->id, $syncDate);
                }

                $processed++;
            }
        });

        $this->info(sprintf('%d pasta(s) processada(s).', $processed));

        return self::SUCCESS;
    }
}

<?php

namespace App\Http\Controllers;

use App\Integrations\DataJud\DataJudEndpointResolver;
use App\Jobs\SyncFolderDataJud;
use App\Models\Folder;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Bus;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class DataJudSyncController extends Controller
{
    public function store(
        Folder $folder,
        DataJudEndpointResolver $resolver,
    ): JsonResponse {
        try {
            $resolver->resolve((string) $folder->process_number);
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'process_number' => [$exception->getMessage()],
            ]);
        }

        $timezone = (string) config('services.datajud.timezone', 'America/Sao_Paulo');

        Bus::dispatchSync(new SyncFolderDataJud(
            $folder->id,
            CarbonImmutable::now($timezone)->toDateString(),
        ));

        return response()->json([
            'message' => 'Sincronização com o DataJud concluída com sucesso.',
            'queued' => false,
        ]);
    }
}

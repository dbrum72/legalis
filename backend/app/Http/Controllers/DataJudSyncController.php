<?php

namespace App\Http\Controllers;

use App\Models\Folder;
use App\Services\Folders\SyncFolderWithDataJud;
use Illuminate\Http\JsonResponse;

class DataJudSyncController extends Controller
{
    public function store(
        Folder $folder,
        SyncFolderWithDataJud $service,
    ): JsonResponse {
        return response()->json(
            $service->execute($folder),
        );
    }
}

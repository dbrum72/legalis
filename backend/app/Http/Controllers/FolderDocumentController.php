<?php

namespace App\Http\Controllers;

use App\Http\Requests\FolderDocumentStoreRequest;
use App\Models\Folder;
use App\Models\FolderDocument;
use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FolderDocumentController extends Controller
{
    public function __construct(
        private readonly CurrentOrganization $currentOrganization,
    ) {}

    public function index(
        Folder $folder,
    ): JsonResponse {
        $documents =
            $folder
            ->documents()
            ->with([
                'user:id,name',
            ])
            ->latest()
            ->get([
                'id',
                'folder_id',
                'user_id',
                'name',
                'original_name',
                'path',
                'mime_type',
                'size',
                'description',
                'created_at',
                'updated_at',
            ]);

        return response()->json(
            $documents
        );
    }

    public function store(
        FolderDocumentStoreRequest $request,
        Folder $folder,
    ): JsonResponse {
        $file =
            $request->file(
                'file'
            );

        $organization =
            $this
            ->currentOrganization
            ->get();

        $extension =
            $file
            ->getClientOriginalExtension();

        $storedName =
            Str::uuid()
            ->toString();

        if ($extension) {
            $storedName .=
                '.' . strtolower(
                    $extension
                );
        }

        $directory =
            implode(
                '/',
                [
                    'organizations',
                    $organization->id,
                    'folders',
                    $folder->id,
                    'documents',
                ],
            );

        $path =
            $file->storeAs(
                $directory,
                $storedName,
                'local',
            );

        if (!$path) {
            return response()->json(
                [
                    'message' =>
                    'Não foi possível armazenar o documento.',
                ],
                500,
            );
        }

        $user =
            $request->user(
                'api'
            );

        try {
            $document =
                $folder
                ->documents()
                ->create([
                    'user_id' =>
                    $user?->id,

                    'name' =>
                    $request
                        ->validated(
                            'name'
                        ),

                    'original_name' =>
                    $file
                        ->getClientOriginalName(),

                    'path' =>
                    $path,

                    'mime_type' =>
                    $file
                        ->getMimeType()
                        ??
                        $file
                        ->getClientMimeType()
                        ??
                        'application/octet-stream',

                    'size' =>
                    $file
                        ->getSize(),

                    'description' =>
                    $request
                        ->validated(
                            'description'
                        ),
                ]);

            $document->load([
                'user:id,name',
            ]);
        } catch (\Throwable $exception) {
            Storage::disk(
                'local'
            )
                ->delete(
                    $path
                );

            throw $exception;
        }

        return response()->json(
            $document,
            201,
        );
    }

    public function download(
        Folder $folder,
        FolderDocument $document,
    ): StreamedResponse {
        $this
            ->ensureDocumentBelongsToFolder(
                $folder,
                $document,
            );

        if (
            !Storage::disk(
                'local'
            )->exists(
                $document->path
            )
        ) {
            abort(
                404
            );
        }

        return Storage::disk(
            'local'
        )->download(
            $document->path,
            $document->original_name,
        );
    }

    public function destroy(
        Folder $folder,
        FolderDocument $document,
    ): JsonResponse {
        $this
            ->ensureDocumentBelongsToFolder(
                $folder,
                $document,
            );

        $path =
            $document->path;

        $document->delete();

        if (
            $path &&
            Storage::disk(
                'local'
            )->exists(
                $path
            )
        ) {
            Storage::disk(
                'local'
            )->delete(
                $path
            );
        }

        return response()->json(
            null,
            204,
        );
    }

    private function ensureDocumentBelongsToFolder(
        Folder $folder,
        FolderDocument $document,
    ): void {
        if (
            (int) $document->folder_id !==
            (int) $folder->id
        ) {
            abort(
                404
            );
        }
    }
}

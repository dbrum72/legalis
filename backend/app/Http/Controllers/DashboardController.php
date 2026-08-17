<?php

namespace App\Http\Controllers;

use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Http\JsonResponse;

class DashboardController extends Controller
{
    public function index(
        CurrentOrganization $currentOrganization,
    ): JsonResponse {
        $organization =
            $currentOrganization->get();

        $recentFolders =
            $organization
            ->folders()
            ->orderByDesc('created_at')
            ->limit(5)
            ->get([
                'id',
                'name',
                'process_number',
                'created_at',
            ]);

        return response()->json([
            'summary' => [
                'clients' =>
                $organization
                    ->clients()
                    ->count(),

                'folders' =>
                $organization
                    ->folders()
                    ->count(),

                'active_members' =>
                $organization
                    ->users()
                    ->wherePivot(
                        'status',
                        'active',
                    )
                    ->count(),
            ],

            'recent_folders' =>
            $recentFolders,
        ]);
    }
}

<?php

namespace App\Http\Controllers;

use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Role;

class OrganizationRoleController extends Controller
{
    public function index(
        CurrentOrganization $currentOrganization,
    ): JsonResponse {
        $organization =
            $currentOrganization->get();

        $roles =
            Role::query()
            ->where(
                'organization_id',
                $organization->getKey(),
            )
            ->where(
                'guard_name',
                'api',
            )
            ->orderBy('name')
            ->get([
                'id',
                'name',
            ])
            ->map(
                fn(Role $role): array => [
                    'id' =>
                    $role->getKey(),

                    'name' =>
                    $role->name,
                ]
            )
            ->values();

        return response()->json(
            $roles
        );
    }
}

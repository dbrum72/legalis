<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrganizationRolePermissionsUpdateRequest;
use App\Support\Organizations\OrganizationRoleDefinitions;
use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Http\JsonResponse;
use Spatie\Permission\Models\Permission;
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
                ->withCount('permissions')
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                    'description',
                ])
                ->map(
                    fn (Role $role): array => [
                        'id' => $role->getKey(),

                        'name' => $role->name,

                        'description' => $role->description,

                        'permissions_count' => $role->permissions_count,
                    ]
                )
                ->values();

        return response()->json(
            $roles
        );
    }

    public function show(
        Role $role,
        CurrentOrganization $currentOrganization,
    ): JsonResponse {
        $this->ensureRoleBelongsToOrganization(
            $role,
            $currentOrganization,
        );

        return response()->json(
            $this->rolePayload($role)
        );
    }

    public function updatePermissions(
        OrganizationRolePermissionsUpdateRequest $request,
        Role $role,
        CurrentOrganization $currentOrganization,
    ): JsonResponse {
        $this->ensureRoleBelongsToOrganization(
            $role,
            $currentOrganization,
        );

        $permissions =
            Permission::query()
                ->where('guard_name', 'api')
                ->whereIn(
                    'name',
                    $request->validated('permissions'),
                )
                ->get();

        $role->syncPermissions($permissions);

        return response()->json(
            $this->rolePayload($role->refresh())
        );
    }

    private function ensureRoleBelongsToOrganization(
        Role $role,
        CurrentOrganization $currentOrganization,
    ): void {
        abort_unless(
            (int) $role->organization_id ===
                (int) $currentOrganization->get()->getKey()
            && $role->guard_name === 'api',
            404,
        );
    }

    private function rolePayload(Role $role): array
    {
        $role->load('permissions');

        return [
            'id' => $role->getKey(),
            'name' => $role->name,
            'description' => $role->description,
            'permissions' => $role->permissions
                ->pluck('name')
                ->sort()
                ->values(),
            'available_permissions' => collect(
                OrganizationRoleDefinitions::permissions()
            )->sort()->values(),
        ];
    }
}

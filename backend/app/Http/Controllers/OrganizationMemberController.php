<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrganizationMemberRoleUpdateRequest;
use App\Http\Requests\OrganizationMemberStatusUpdateRequest;
use App\Models\User;
use App\Services\OrganizationMembers\UpdateOrganizationMemberRole;
use App\Services\OrganizationMembers\UpdateOrganizationMemberStatus;
use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class OrganizationMemberController extends Controller
{
    public function index(
        CurrentOrganization $currentOrganization,
    ): JsonResponse {
        $organization =
            $currentOrganization->get();

        $members =
            $organization
            ->users()
            ->orderBy('users.name')
            ->get()
            ->map(
                function (User $user) use (
                    $organization,
                ): array {
                    return [
                        'id' =>
                        $user->getKey(),

                        'name' =>
                        $user->name,

                        'email' =>
                        $user->email,

                        'status' =>
                        $user
                            ->membership
                            ->status,

                        'joined_at' =>
                        $user
                            ->membership
                            ->joined_at,

                        'role' =>
                        $this->resolveRole(
                            $organization->getKey(),
                            $user->getKey(),
                        ),
                    ];
                }
            )
            ->values();

        return response()->json(
            $members
        );
    }

    public function updateRole(
        OrganizationMemberRoleUpdateRequest $request,
        User $user,
        CurrentOrganization $currentOrganization,
        UpdateOrganizationMemberRole $service,
    ): JsonResponse {
        $organization =
            $currentOrganization->get();

        $service->handle(
            $organization,
            $user,
            $request->validated('role'),
        );

        return response()->json([
            'message' =>
            'Função do membro atualizada com sucesso.',
        ]);
    }

    public function updateStatus(
        OrganizationMemberStatusUpdateRequest $request,
        User $user,
        CurrentOrganization $currentOrganization,
        UpdateOrganizationMemberStatus $service,
    ): JsonResponse {
        $organization =
            $currentOrganization->get();

        $service->handle(
            $organization,
            $user,
            $request->validated('status'),
        );

        return response()->json([
            'message' =>
            'Status do membro atualizado com sucesso.',
        ]);
    }

    private function resolveRole(
        int $organizationId,
        int $userId,
    ): ?string {
        return DB::table('model_has_roles')
            ->join(
                'roles',
                'roles.id',
                '=',
                'model_has_roles.role_id',
            )
            ->where(
                'model_has_roles.organization_id',
                $organizationId,
            )
            ->where(
                'model_has_roles.model_type',
                User::class,
            )
            ->where(
                'model_has_roles.model_id',
                $userId,
            )
            ->value(
                'roles.name'
            );
    }
}

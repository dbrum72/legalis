<?php

namespace App\Services\OrganizationMembers;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Spatie\Permission\Models\Role;

class UpdateOrganizationMemberRole
{
    private const ADMINISTRATIVE_ROLES = [
        'super-admin',
        'socio-administrador',
    ];

    public function handle(
        Organization $organization,
        User $user,
        string $roleName,
    ): void {
        DB::transaction(
            function () use (
                $organization,
                $user,
                $roleName,
            ): void {
                $membership =
                    $organization
                    ->users()
                    ->whereKey(
                        $user->getKey()
                    )
                    ->first();

                if ($membership === null) {
                    throw ValidationException::withMessages([
                        'member' => [
                            'O usuário não pertence à organização atual.',
                        ],
                    ]);
                }

                $role =
                    Role::query()
                    ->where(
                        'organization_id',
                        $organization->getKey()
                    )
                    ->where(
                        'guard_name',
                        'api'
                    )
                    ->where(
                        'name',
                        $roleName
                    )
                    ->firstOrFail();

                $this->ensureAdministrationRemains(
                    $organization,
                    $user,
                    $membership->membership->status,
                    $roleName,
                );

                $this->syncRole(
                    $organization,
                    $user,
                    $role,
                );
            }
        );
    }

    private function ensureAdministrationRemains(
        Organization $organization,
        User $user,
        string $membershipStatus,
        string $newRoleName,
    ): void {
        if ($membershipStatus !== 'active') {
            return;
        }

        if (
            !$this->userHasAdministrativeRole(
                $organization,
                $user,
            )
        ) {
            return;
        }

        if (
            in_array(
                $newRoleName,
                self::ADMINISTRATIVE_ROLES,
                true,
            )
        ) {
            return;
        }

        if (
            $this->hasAnotherActiveAdministrator(
                $organization,
                $user,
            )
        ) {
            return;
        }

        throw ValidationException::withMessages([
            'role' => [
                'Não é possível remover a função administrativa do último administrador ativo da organização.',
            ],
        ]);
    }

    private function userHasAdministrativeRole(
        Organization $organization,
        User $user,
    ): bool {
        return DB::table('model_has_roles')
            ->join(
                'roles',
                'roles.id',
                '=',
                'model_has_roles.role_id',
            )
            ->where(
                'model_has_roles.organization_id',
                $organization->getKey(),
            )
            ->where(
                'model_has_roles.model_type',
                User::class,
            )
            ->where(
                'model_has_roles.model_id',
                $user->getKey(),
            )
            ->whereIn(
                'roles.name',
                self::ADMINISTRATIVE_ROLES,
            )
            ->exists();
    }

    private function hasAnotherActiveAdministrator(
        Organization $organization,
        User $user,
    ): bool {
        return DB::table('organization_user')
            ->join(
                'model_has_roles',
                function ($join): void {
                    $join
                        ->on(
                            'model_has_roles.model_id',
                            '=',
                            'organization_user.user_id',
                        )
                        ->where(
                            'model_has_roles.model_type',
                            User::class,
                        );
                }
            )
            ->join(
                'roles',
                'roles.id',
                '=',
                'model_has_roles.role_id',
            )
            ->where(
                'organization_user.organization_id',
                $organization->getKey(),
            )
            ->where(
                'organization_user.status',
                'active',
            )
            ->where(
                'organization_user.user_id',
                '<>',
                $user->getKey(),
            )
            ->whereColumn(
                'model_has_roles.organization_id',
                'organization_user.organization_id',
            )
            ->whereIn(
                'roles.name',
                self::ADMINISTRATIVE_ROLES,
            )
            ->exists();
    }

    private function syncRole(
        Organization $organization,
        User $user,
        Role $role,
    ): void {
        $previousTeamId =
            getPermissionsTeamId();

        try {
            setPermissionsTeamId(
                $organization->getKey()
            );

            $user->unsetRelation(
                'roles'
            );

            $user->syncRoles([
                $role,
            ]);

            $user->unsetRelation(
                'roles'
            );

            $user->unsetRelation(
                'permissions'
            );
        } finally {
            setPermissionsTeamId(
                $previousTeamId
            );
        }
    }
}

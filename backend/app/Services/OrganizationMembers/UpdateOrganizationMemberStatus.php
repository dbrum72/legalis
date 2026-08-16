<?php

namespace App\Services\OrganizationMembers;

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class UpdateOrganizationMemberStatus
{
    private const ADMINISTRATIVE_ROLES = [
        'super-admin',
        'socio-administrador',
    ];

    public function handle(
        Organization $organization,
        User $user,
        string $status,
    ): void {
        DB::transaction(
            function () use (
                $organization,
                $user,
                $status,
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

                if (
                    $status === 'inactive'
                    && $membership
                    ->membership
                    ->status === 'active'
                ) {
                    $this->ensureAdministrationRemains(
                        $organization,
                        $user,
                    );
                }

                $organization
                    ->users()
                    ->updateExistingPivot(
                        $user->getKey(),
                        [
                            'status' =>
                            $status,
                        ]
                    );
            }
        );
    }

    private function ensureAdministrationRemains(
        Organization $organization,
        User $user,
    ): void {
        if (
            !$this->userHasAdministrativeRole(
                $organization,
                $user,
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
            'status' => [
                'Não é possível desativar o último administrador ativo da organização.',
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
}

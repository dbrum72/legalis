<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrganizationInvitationStoreRequest;
use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class OrganizationInvitationController extends Controller
{
    public function store(
        OrganizationInvitationStoreRequest $request,
        CurrentOrganization $currentOrganization,
    ): JsonResponse {
        $organization =
            $currentOrganization->get();

        $user =
            $request->user('api');

        $data =
            $request->validated();

        $invitation =
            DB::transaction(
                function () use (
                    $organization,
                    $user,
                    $data,
                ): OrganizationInvitation {
                    $this
                        ->ensureEmailCanBeInvited(
                            $organization,
                            $data['email'],
                        );

                    $token =
                        Str::random(64);

                    return $organization
                        ->invitations()
                        ->create([
                            'invited_by' =>
                            $user->getKey(),

                            'email' =>
                            $data['email'],

                            'role' =>
                            $data['role'],

                            'token_hash' =>
                            OrganizationInvitation::hashToken(
                                $token
                            ),

                            'status' =>
                            OrganizationInvitation::STATUS_PENDING,

                            'expires_at' =>
                            now()->addDays(
                                OrganizationInvitation::DEFAULT_EXPIRATION_DAYS
                            ),

                            'accepted_at' =>
                            null,

                            'revoked_at' =>
                            null,
                        ]);
                }
            );

        $invitation->load([
            'organization',
            'inviter',
        ]);

        return response()->json(
            $invitation,
            201
        );
    }

    private function ensureEmailCanBeInvited(
        Organization $organization,
        string $email,
    ): void {
        $existingUser =
            User::query()
            ->where(
                'email',
                $email
            )
            ->first();

        if (
            $existingUser !== null
            && $organization
            ->users()
            ->whereKey(
                $existingUser->getKey()
            )
            ->wherePivot(
                'status',
                'active'
            )
            ->exists()
        ) {
            throw ValidationException::withMessages([
                'email' => [
                    'O usuário já possui vínculo ativo com esta organização.',
                ],
            ]);
        }

        $pendingInvitationExists =
            $organization
            ->invitations()
            ->where(
                'email',
                $email
            )
            ->where(
                'status',
                OrganizationInvitation::STATUS_PENDING
            )
            ->where(
                'expires_at',
                '>',
                now()
            )
            ->exists();

        if ($pendingInvitationExists) {
            throw ValidationException::withMessages([
                'email' => [
                    'Já existe um convite pendente para este e-mail.',
                ],
            ]);
        }
    }
}

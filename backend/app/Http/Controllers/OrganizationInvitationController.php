<?php

namespace App\Http\Controllers;

use App\Http\Requests\OrganizationInvitationAcceptRequest;
use App\Http\Requests\OrganizationInvitationStoreRequest;
use App\Models\OrganizationInvitation;
use App\Models\User;
use App\Services\OrganizationInvitations\AcceptOrganizationInvitation;
use App\Services\OrganizationInvitations\IssueOrganizationInvitation;
use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class OrganizationInvitationController extends Controller
{
    public function store(
        OrganizationInvitationStoreRequest $request,
        CurrentOrganization $currentOrganization,
        IssueOrganizationInvitation $issuer,
    ): JsonResponse {
        $invitation =
            $issuer->execute(
                organization: $currentOrganization->get(),

                inviter: $request->user('api'),

                email: $request->validated(
                    'email'
                ),

                role: $request->validated(
                    'role'
                ),
            );

        return response()->json(
            $invitation,
            201
        );
    }

    public function showAcceptance(
        string $token,
    ): JsonResponse {
        $invitation =
            $this->resolveInvitation(
                $token
            );

        $this->ensureAcceptable(
            $invitation
        );

        $registrationRequired =
            !User::query()
                ->where(
                    'email',
                    $invitation->email
                )
                ->exists();

        return response()->json([
            'email' =>
            $invitation->email,

            'role' =>
            $invitation->role,

            'registration_required' =>
            $registrationRequired,

            'organization' => [
                'id' =>
                $invitation
                    ->organization
                    ->id,

                'name' =>
                $invitation
                    ->organization
                    ->name,

                'slug' =>
                $invitation
                    ->organization
                    ->slug,
            ],
        ]);
    }

    public function accept(
        OrganizationInvitationAcceptRequest $request,
        string $token,
        AcceptOrganizationInvitation $acceptor,
    ): JsonResponse {
        $invitation = $this->resolveInvitation($token);

        $this->ensureAcceptable($invitation);

        $registrationRequired =
            !User::query()
                ->where(
                    'email',
                    $invitation->email
                )
                ->exists();

        $user =
            $acceptor->execute(
                invitation: $invitation,

                data: $request->validated(),
            );

        $organization = [
            'id' =>$invitation->organization->id,
            'name' => $invitation->organization->name,
            'slug' => $invitation->organization->slug,
        ];

        $response = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
            ],
            'organization' => $organization,
            'role' => $invitation->role,
        ];

        if ($registrationRequired) {
            $guard =
                Auth::guard(
                    'api'
                );

            $authToken = $guard->login($user);

            $response = [
                'token' => $authToken,
                'access_token' => $authToken,
                'token_type' => 'bearer',
                'expires_in' => $guard->factory()->getTTL() * 60,
                'organizations' => [$organization],
                ...$response,
            ];
        }

        return response()->json($response);
    }

    private function resolveInvitation(
        string $token,
    ): OrganizationInvitation {
        return OrganizationInvitation::query()
            ->with(
                'organization'
            )
            ->where(
                'token_hash',
                OrganizationInvitation::hashToken(
                    $token
                )
            )
            ->firstOrFail();
    }

    private function ensureAcceptable(
        OrganizationInvitation $invitation,
    ): void {
        if (
            !$invitation->isAcceptable()
        ) {
            abort(
                410,
                'Este convite não está mais disponível.'
            );
        }
    }
}

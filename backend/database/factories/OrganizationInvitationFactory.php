<?php

namespace Database\Factories;

use App\Models\Organization;
use App\Models\OrganizationInvitation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<OrganizationInvitation>
 */
class OrganizationInvitationFactory extends Factory
{
    protected $model =
    OrganizationInvitation::class;

    public function definition(): array
    {
        $token =
            Str::random(64);

        return [
            'organization_id' =>
            Organization::factory(),

            'invited_by' =>
            User::factory(),

            'email' =>
            fake()
                ->unique()
                ->safeEmail(),

            'role' =>
            'advogado-junior',

            'token_hash' =>
            OrganizationInvitation::hashToken(
                $token
            ),

            'status' =>
            OrganizationInvitation::STATUS_PENDING,

            'expires_at' =>
            now()->addDays(7),

            'accepted_at' =>
            null,

            'revoked_at' =>
            null,
        ];
    }

    public function accepted(): static
    {
        return $this->state(
            fn(): array => [
                'status' =>
                OrganizationInvitation::STATUS_ACCEPTED,

                'accepted_at' =>
                now(),

                'revoked_at' =>
                null,
            ]
        );
    }

    public function revoked(): static
    {
        return $this->state(
            fn(): array => [
                'status' =>
                OrganizationInvitation::STATUS_REVOKED,

                'accepted_at' =>
                null,

                'revoked_at' =>
                now(),
            ]
        );
    }

    public function expired(): static
    {
        return $this->state(
            fn(): array => [
                'status' =>
                OrganizationInvitation::STATUS_PENDING,

                'expires_at' =>
                now()->subMinute(),

                'accepted_at' =>
                null,

                'revoked_at' =>
                null,
            ]
        );
    }
}

<?php

namespace App\Http\Requests;

use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class OrganizationInvitationStoreRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $email = $this->input(
            'email'
        );

        $role = $this->input(
            'role'
        );

        $this->merge([
            'email' =>
            is_string($email)
                ? mb_strtolower(
                    trim($email)
                )
                : $email,

            'role' =>
            is_string($role)
                ? trim($role)
                : $role,
        ]);
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $organizationId =
            app(
                CurrentOrganization::class
            )->id();

        return [
            'email' => [
                'required',
                'string',
                'email',
                'max:255',
            ],

            'role' => [
                'required',
                'string',
                'max:100',

                Rule::exists(
                    'roles',
                    'name'
                )->where(
                    fn($query) =>
                    $query
                        ->where(
                            'organization_id',
                            $organizationId
                        )
                        ->where(
                            'guard_name',
                            'api'
                        )
                ),
            ],
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LegalPublicationLinkRequest extends FormRequest
{
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
            'folder_id' => [
                'nullable',
                'integer',

                Rule::exists(
                    'folders',
                    'id',
                )->where(
                    'organization_id',
                    $organizationId,
                ),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'folder_id.exists' => 'A pasta informada não pertence à organização atual.',
        ];
    }
}

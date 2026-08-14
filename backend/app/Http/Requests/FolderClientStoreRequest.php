<?php

namespace App\Http\Requests;

use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FolderClientStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $organizationId = $this
            ->container
            ->make(
                CurrentOrganization::class
            )
            ->id();

        $folderId = $this->route(
            'folder'
        );

        return [
            'client_id' => [
                'required',
                'integer',

                Rule::exists(
                    'clients',
                    'id',
                )->where(
                    'organization_id',
                    $organizationId,
                ),

                Rule::unique(
                    'folder_clients',
                    'client_id',
                )->where(
                    fn($query) =>
                    $query
                        ->where(
                            'folder_id',
                            $folderId,
                        )
                        ->where(
                            'qualification_id',
                            $this->input(
                                'qualification_id'
                            ),
                        )
                ),
            ],

            'qualification_id' => [
                'required',
                'integer',
                'exists:qualifications,id',
            ],
        ];
    }
}

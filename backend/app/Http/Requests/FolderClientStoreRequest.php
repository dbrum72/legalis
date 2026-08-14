<?php

namespace App\Http\Requests;

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
        $folder = $this->route('folder');

        $folderId = $folder instanceof \App\Models\Folder
            ? $folder->getKey()
            : $folder;

        return [
            'client_id' => [
                'required',
                'integer',
                'exists:clients,id',

                Rule::unique('folder_clients')
                    ->where(
                        fn($query) => $query
                            ->where('folder_id', $folderId)
                            ->where(
                                'qualification_id',
                                $this->input('qualification_id')
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

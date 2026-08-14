<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FolderStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'process_number' => [
                'nullable',
                'string',
                'max:25',
            ],
        ];
    }
}

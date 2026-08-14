<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FolderClientUpdateRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'qualification_id' => [
                'required',
                'integer',
                'exists:qualifications,id',
            ],
        ];
    }
}

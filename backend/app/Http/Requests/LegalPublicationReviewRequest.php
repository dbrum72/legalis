<?php

namespace App\Http\Requests;

use App\Models\LegalPublication;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LegalPublicationReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'review_status' => [
                'required',

                Rule::in([
                    LegalPublication::REVIEWED,
                    LegalPublication::IGNORED,
                ]),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'review_status.required' => 'Informe o resultado da conferência.',

            'review_status.in' => 'O resultado da conferência é inválido.',
        ];
    }
}

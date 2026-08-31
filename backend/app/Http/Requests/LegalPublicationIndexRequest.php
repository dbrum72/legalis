<?php

namespace App\Http\Requests;

use App\Models\LegalPublication;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class LegalPublicationIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $availableToRules = [
            'nullable',
            'date_format:Y-m-d',
        ];

        if ($this->filled('available_from')) {
            $availableToRules[] =
                'after_or_equal:available_from';
        }

        return [
            'link_status' => [
                'nullable',

                Rule::in([
                    'linked',
                    'unlinked',
                ]),
            ],

            'review_status' => [
                'nullable',

                Rule::in([
                    LegalPublication::REVIEW_PENDING,
                    LegalPublication::REVIEWED,
                    LegalPublication::IGNORED,
                ]),
            ],

            'available_from' => [
                'nullable',
                'date_format:Y-m-d',
            ],

            'available_to' => $availableToRules,

            'search' => [
                'nullable',
                'string',
                'max:100',
            ],

            'per_page' => [
                'nullable',
                'integer',
                'min:1',
                'max:100',
            ],
        ];
    }
}

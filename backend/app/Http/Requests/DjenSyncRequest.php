<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class DjenSyncRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $toRules = [
            'nullable',
            'date_format:Y-m-d',
        ];

        if ($this->filled('from')) {
            $toRules[] =
                'after_or_equal:from';
        }

        return [
            'from' => [
                'nullable',
                'date_format:Y-m-d',
            ],

            'to' => $toRules,
        ];
    }

    public function messages(): array
    {
        return [
            'from.date_format' => 'A data inicial deve estar no formato AAAA-MM-DD.',

            'to.date_format' => 'A data final deve estar no formato AAAA-MM-DD.',

            'to.after_or_equal' => 'A data final não pode ser anterior à data inicial.',
        ];
    }
}

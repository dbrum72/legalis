<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class TimeEntryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $presence = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'worked_on' => [$presence, 'date'],
            'duration_minutes' => [$presence, 'integer', 'between:1,1440'],
            'description' => [$presence, 'string', 'max:500'],
            'hourly_rate_cents' => ['sometimes', 'integer', 'min:0'],
            'billable' => ['sometimes', 'boolean'],
            'status' => ['sometimes', Rule::in(['open', 'cancelled'])],
        ];
    }
}

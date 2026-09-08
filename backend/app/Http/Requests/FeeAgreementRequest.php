<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FeeAgreementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $presence = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'client_id' => ['nullable', 'integer'],
            'type' => [$presence, Rule::in(['hourly', 'fixed', 'contingency', 'hybrid'])],
            'status' => ['sometimes', Rule::in(['draft', 'active', 'closed', 'cancelled'])],
            'hourly_rate_cents' => ['nullable', 'integer', 'min:0', 'required_if:type,hourly'],
            'fixed_fee_cents' => ['nullable', 'integer', 'min:0', 'required_if:type,fixed'],
            'contingency_percentage' => ['nullable', 'numeric', 'min:0.01', 'max:100', 'required_if:type,contingency'],
            'billing_day' => ['nullable', 'integer', 'between:1,31'],
            'starts_on' => ['nullable', 'date'],
            'ends_on' => ['nullable', 'date', 'after_or_equal:starts_on'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ExpenseRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $presence = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'incurred_on' => [$presence, 'date'],
            'description' => [$presence, 'string', 'max:500'],
            'amount_cents' => [$presence, 'integer', 'min:1'],
            'reimbursable' => ['sometimes', 'boolean'],
            'receipt_path' => ['nullable', 'string', 'max:255'],
            'status' => ['sometimes', Rule::in(['open', 'cancelled'])],
        ];
    }
}

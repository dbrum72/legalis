<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FolderBillingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'client_id' => ['required', 'integer'],
            'fee_agreement_id' => ['nullable', 'integer'],
            'due_on' => ['required', 'date'],
            'discount_cents' => ['sometimes', 'integer', 'min:0'],
            'time_entry_ids' => ['sometimes', 'array'],
            'time_entry_ids.*' => ['integer', 'distinct'],
            'expense_ids' => ['sometimes', 'array'],
            'expense_ids.*' => ['integer', 'distinct'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }
}

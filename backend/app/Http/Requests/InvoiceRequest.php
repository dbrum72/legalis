<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class InvoiceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $presence = $this->isMethod('post') ? 'required' : 'sometimes';

        return [
            'client_id' => [$presence, 'integer'],
            'folder_id' => ['nullable', 'integer'],
            'fee_agreement_id' => ['nullable', 'integer'],
            'issued_on' => ['nullable', 'date'],
            'due_on' => [$presence, 'date'],
            'status' => ['sometimes', Rule::in(['draft', 'open', 'cancelled'])],
            'subtotal_cents' => [$presence, 'integer', 'min:1'],
            'discount_cents' => ['sometimes', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:10000'],
            'installment_count' => $this->isMethod('post')
                ? ['sometimes', 'integer', 'min:1', 'max:120', 'lte:subtotal_cents']
                : ['prohibited'],
            'installment_interval_months' => $this->isMethod('post')
                ? ['sometimes', 'integer', 'min:1', 'max:12']
                : ['prohibited'],
        ];
    }
}

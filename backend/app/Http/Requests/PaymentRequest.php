<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PaymentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'paid_at' => ['required', 'date'],
            'amount_cents' => ['required', 'integer', 'min:1'],
            'method' => ['required', Rule::in(['pix', 'bank_transfer', 'cash', 'credit_card', 'debit_card', 'boleto', 'other'])],
            'reference' => ['nullable', 'string', 'max:120'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }
}

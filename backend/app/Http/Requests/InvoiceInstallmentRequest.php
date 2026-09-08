<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class InvoiceInstallmentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'due_on' => ['required', 'date'],
            'subtotal_cents' => ['required', 'integer', 'min:1'],
            'discount_cents' => ['sometimes', 'integer', 'min:0'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }
}

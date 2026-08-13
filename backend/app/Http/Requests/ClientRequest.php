<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClientRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $clientId = $this->route('client')?->id
            ?? $this->route('client');

        return [
            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'document' => [
                'required',
                'string',
                'max:14',
                Rule::unique('clients', 'document')
                    ->ignore($clientId),
            ],

            'identity_document' => [
                'nullable',
                'string',
                'max:20',
            ],

            'identity_issuer' => [
                'nullable',
                'string',
                'max:30',
            ],

            'marital_status_id' => [
                'nullable',
                'integer',
                'exists:marital_statuses,id',
            ],

            'profession' => [
                'nullable',
                'string',
                'max:100',
            ],

            'address' => [
                'nullable',
                'string',
                'max:150',
            ],

            'address_complement' => [
                'nullable',
                'string',
                'max:100',
            ],

            'district' => [
                'nullable',
                'string',
                'max:100',
            ],

            'city' => [
                'nullable',
                'string',
                'max:100',
            ],

            'postal_code' => [
                'nullable',
                'string',
                'max:8',
            ],

            'phone' => [
                'nullable',
                'string',
                'max:11',
            ],

            'whatsapp' => [
                'boolean',
            ],

            'email' => [
                'nullable',
                'email',
                'max:255',
            ],
        ];
    }
}

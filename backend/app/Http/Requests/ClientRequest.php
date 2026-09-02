<?php

namespace App\Http\Requests;

use App\Models\Client;
use App\Rules\ValidCpf;
use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ClientRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        if ($this->has('document')) {
            $this->merge([
                'document' => preg_replace(
                    '/\D/',
                    '',
                    (string) $this->input('document'),
                ),
            ]);
        }
    }

    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $organizationId = $this
            ->container
            ->make(
                CurrentOrganization::class
            )
            ->id();

        $client =
            $this->route('client');

        $clientId =
            $client instanceof Client
            ? $client->getKey()
            : $client;

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
                'regex:/^(?:\d{11}|\d{14})$/',
                new ValidCpf,

                Rule::unique(
                    'clients',
                    'document',
                )
                    ->where(
                        'organization_id',
                        $organizationId,
                    )
                    ->ignore(
                        $clientId
                    ),
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

    public function messages(): array
    {
        return [
            'document.regex' => 'Informe um CPF ou CNPJ com 11 ou 14 dígitos.',
        ];
    }
}

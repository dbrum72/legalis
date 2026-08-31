<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FolderStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => [
                'required',
                'string',
                'max:100',
            ],

            'process_number' => [
                'nullable',
                'string',
                'max:25',
                Rule::when(
                    $this->boolean('datajud_monitoring_enabled'),
                    ['required', 'regex:/^\d{7}-\d{2}\.\d{4}\.\d\.\d{2}\.\d{4}$/'],
                ),
            ],

            'datajud_monitoring_enabled' => [
                'sometimes',
                'boolean',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'process_number.required' => 'Informe o número CNJ para habilitar o monitoramento DataJud.',
            'process_number.regex' => 'Informe um número processual CNJ válido para o monitoramento DataJud.',
        ];
    }
}

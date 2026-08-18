<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FolderTaskStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' =>
            is_string(
                $this->input('title')
            )
                ? trim(
                    $this->input('title')
                )
                : $this->input('title'),

            'description' =>
            is_string(
                $this->input('description')
            )
                ? trim(
                    $this->input('description')
                )
                : $this->input('description'),

            'priority' =>
            is_string(
                $this->input('priority')
            )
                ? trim(
                    $this->input('priority')
                )
                : $this->input('priority'),
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => [
                'required',
                'string',
                'max:180',
            ],

            'description' => [
                'nullable',
                'string',
                'max:10000',
            ],

            'priority' => [
                'nullable',
                'string',
                Rule::in([
                    'low',
                    'medium',
                    'high',
                ]),
            ],

            'due_at' => [
                'nullable',
                'date',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' =>
            'Informe o título da tarefa.',

            'title.max' =>
            'O título da tarefa não pode ultrapassar 180 caracteres.',

            'description.max' =>
            'A descrição não pode ultrapassar 10.000 caracteres.',

            'priority.in' =>
            'A prioridade informada é inválida.',

            'due_at.date' =>
            'A data de vencimento da tarefa é inválida.',
        ];
    }
}

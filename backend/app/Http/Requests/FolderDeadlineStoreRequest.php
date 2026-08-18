<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FolderDeadlineStoreRequest extends FormRequest
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

            'due_at' => [
                'required',
                'date',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'title.required' =>
            'Informe o título do prazo.',

            'title.string' =>
            'O título do prazo é inválido.',

            'title.max' =>
            'O título do prazo não pode ultrapassar 180 caracteres.',

            'description.string' =>
            'A descrição do prazo é inválida.',

            'description.max' =>
            'A descrição não pode ultrapassar 10.000 caracteres.',

            'due_at.required' =>
            'Informe o vencimento do prazo.',

            'due_at.date' =>
            'O vencimento do prazo é inválido.',
        ];
    }
}

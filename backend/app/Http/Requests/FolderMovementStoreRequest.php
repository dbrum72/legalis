<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FolderMovementStoreRequest extends FormRequest
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
            'occurred_at' => [
                'required',
                'date',
            ],

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
        ];
    }

    public function messages(): array
    {
        return [
            'occurred_at.required' =>
            'Informe a data da movimentação.',

            'occurred_at.date' =>
            'A data da movimentação é inválida.',

            'title.required' =>
            'Informe o título da movimentação.',

            'title.string' =>
            'O título da movimentação é inválido.',

            'title.max' =>
            'O título da movimentação não pode ultrapassar 180 caracteres.',

            'description.string' =>
            'A descrição da movimentação é inválida.',

            'description.max' =>
            'A descrição não pode ultrapassar 10.000 caracteres.',
        ];
    }
}

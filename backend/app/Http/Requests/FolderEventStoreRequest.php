<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FolderEventStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'type' =>
            is_string(
                $this->input('type')
            )
                ? trim(
                    $this->input('type')
                )
                : $this->input('type'),

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

            'location' =>
            is_string(
                $this->input('location')
            )
                ? trim(
                    $this->input('location')
                )
                : $this->input('location'),
        ]);
    }

    public function rules(): array
    {
        return [
            'type' => [
                'required',
                'string',
                Rule::in([
                    'hearing',
                    'meeting',
                    'expert_exam',
                    'diligence',
                    'other',
                ]),
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

            'starts_at' => [
                'required',
                'date',
            ],

            'ends_at' => [
                'nullable',
                'date',
                'after_or_equal:starts_at',
            ],

            'location' => [
                'nullable',
                'string',
                'max:255',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'type.required' =>
            'Informe o tipo do evento.',

            'type.in' =>
            'O tipo do evento é inválido.',

            'title.required' =>
            'Informe o título do evento.',

            'title.max' =>
            'O título do evento não pode ultrapassar 180 caracteres.',

            'description.max' =>
            'A descrição não pode ultrapassar 10.000 caracteres.',

            'starts_at.required' =>
            'Informe o início do evento.',

            'starts_at.date' =>
            'A data de início do evento é inválida.',

            'ends_at.date' =>
            'A data de término do evento é inválida.',

            'ends_at.after_or_equal' =>
            'O término do evento não pode ocorrer antes do início.',

            'location.max' =>
            'O local não pode ultrapassar 255 caracteres.',
        ];
    }
}

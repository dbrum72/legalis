<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class FolderDocumentStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        /*
         * A autorização de domínio será aplicada
         * na rota por meio de folders.update.
         */
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' =>
            is_string(
                $this->input('name')
            )
                ? trim(
                    $this->input('name')
                )
                : $this->input('name'),

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
            'file' => [
                'required',
                'file',
                'max:20480',
                'mimes:pdf,doc,docx,xls,xlsx,jpg,jpeg,png,txt',
            ],

            'name' => [
                'required',
                'string',
                'max:150',
            ],

            'description' => [
                'nullable',
                'string',
                'max:5000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' =>
            'Selecione um arquivo.',

            'file.file' =>
            'O arquivo informado é inválido.',

            'file.max' =>
            'O arquivo não pode ultrapassar 20 MB.',

            'file.mimes' =>
            'O tipo de arquivo informado não é permitido.',

            'name.required' =>
            'Informe o nome do documento.',

            'name.string' =>
            'O nome do documento é inválido.',

            'name.max' =>
            'O nome do documento não pode ultrapassar 150 caracteres.',

            'description.string' =>
            'A descrição do documento é inválida.',

            'description.max' =>
            'A descrição não pode ultrapassar 5.000 caracteres.',
        ];
    }
}

<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\HasFinancialClassificationRules;
use App\Models\Folder;
use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class PayableRequest extends FormRequest
{
    use HasFinancialClassificationRules;

    public function rules(): array
    {
        $organization = app(CurrentOrganization::class)->id();

        return $this->classificationRules() + [
            'supplier' => ['required', 'string', 'max:180'],
            'description' => ['required', 'string', 'max:500'],
            'category' => ['nullable', 'string', 'max:80'],
            'folder_id' => ['nullable', 'integer', Rule::exists('folders', 'id')->where('organization_id', $organization)],
            'client_id' => ['nullable', 'integer', Rule::exists('clients', 'id')->where('organization_id', $organization)],
            'due_on' => ['required', 'date'],
            'amount_cents' => ['required', 'integer', 'min:1'],
            'notes' => ['nullable', 'string', 'max:10000'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }
            $folderId = $this->input('folder_id', $this->route('payable')?->folder_id);
            $clientId = $this->input('client_id', $this->route('payable')?->client_id);
            if ($folderId && $clientId && ! Folder::find($folderId)?->clients()->whereKey($clientId)->exists()) {
                $validator->errors()->add('client_id', 'O cliente deve estar vinculado à pasta selecionada.');
            }
        }];
    }
}

<?php

namespace App\Http\Requests\Concerns;

use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Validation\Rule;

trait HasFinancialClassificationRules
{
    protected function classificationRules(): array
    {
        $organizationId = app(CurrentOrganization::class)->id();
        $record = $this->route('payable') ?? $this->route('expense');
        $rules = [];
        foreach (['category_id' => 'category', 'cost_center_id' => 'cost_center'] as $field => $kind) {
            $exists = Rule::exists('financial_classifications', 'id')->where('organization_id', $organizationId)->where('kind', $kind);
            // A classification deactivated later remains valid on its existing records.
            if (! $record || (int) $record->{$field} !== (int) $this->input($field)) {
                $exists->where('active', true);
            }
            $rules[$field] = ['nullable', 'integer', $exists];
        }

        return $rules;
    }
}

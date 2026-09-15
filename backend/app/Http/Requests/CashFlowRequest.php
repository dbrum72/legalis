<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CashFlowRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'month' => ['nullable', 'date_format:Y-m', Rule::prohibitedIf($this->filled('from') || $this->filled('to'))],
            'from' => ['nullable', 'required_with:to', 'date_format:Y-m-d'],
            'to' => ['nullable', 'required_with:from', 'date_format:Y-m-d', 'after_or_equal:from'],
            'page' => ['sometimes', 'integer', 'min:1'],
        ];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty() || ! $this->filled('from') || ! $this->filled('to')) {
                return;
            }
            if (CarbonImmutable::parse($this->input('from'))->diffInDays(CarbonImmutable::parse($this->input('to'))) > 365) {
                $validator->errors()->add('to', 'Selecione um período de até 366 dias.');
            }
        }];
    }

    public function period(): array
    {
        $data = $this->validated();
        if (! empty($data['from'])) {
            return ['from' => $data['from'], 'to' => $data['to']];
        }
        $month = ! empty($data['month'])
            ? CarbonImmutable::createFromFormat('!Y-m', $data['month'])
            : CarbonImmutable::now()->startOfMonth();

        return ['from' => $month->toDateString(), 'to' => $month->endOfMonth()->toDateString()];
    }
}

<?php

namespace App\Http\Requests;

use Illuminate\Validation\Rule;

class PayableRecurrenceRequest extends PayableRequest
{
    public function rules(): array
    {
        return array_replace(parent::rules(), [
            'due_on' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.today('America/Sao_Paulo')->toDateString(), 'before_or_equal:'.today('America/Sao_Paulo')->addYears(5)->toDateString()],
            'interval_months' => ['required', 'integer', Rule::in([1, 3, 6, 12])],
            'ends_on' => ['nullable', 'date_format:Y-m-d', 'after_or_equal:due_on', 'before:9999-01-01'],
        ]);
    }
}

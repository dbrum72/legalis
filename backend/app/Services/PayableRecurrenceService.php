<?php

namespace App\Services;

use App\Models\Payable;
use App\Models\PayableRecurrence;
use App\Support\Tenancy\CurrentOrganization;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class PayableRecurrenceService
{
    public function nextDate(PayableRecurrence $rule, CarbonImmutable $date): ?CarbonImmutable
    {
        // Calculate from the original day, never from February's clamped day.
        $next = $date->startOfMonth()->addMonths($rule->interval_months);
        $next = $next->day(min($rule->starts_on->day, $next->daysInMonth));

        return $next->year >= 9999 || ($rule->ends_on && $next->gt($rule->ends_on)) ? null : $next;
    }

    public function preview(PayableRecurrence $rule, CarbonImmutable $through): array
    {
        $dates = [];
        $date = $rule->next_due_on;
        while ($date && $date->lte($through) && count($dates) < 120) {
            $dates[] = $date->toDateString();
            $date = $this->nextDate($rule, $date);
        }

        return $dates;
    }

    public function generate(PayableRecurrence $rule, CarbonImmutable $through): int
    {
        return DB::transaction(function () use ($rule, $through): int {
            $rule = PayableRecurrence::query()->lockForUpdate()->findOrFail($rule->id);
            if (! $rule->active || ! $rule->next_due_on || $rule->next_due_on->gt($through)) {
                return 0;
            }
            $organizationId = app(CurrentOrganization::class)->id();
            $data = $rule->template;
            $rules = [];
            foreach (['category_id' => 'category', 'cost_center_id' => 'cost_center'] as $field => $kind) {
                $rules[$field] = ['nullable', Rule::exists('financial_classifications', 'id')->where('organization_id', $organizationId)->where('kind', $kind)->where('active', true)];
            }
            foreach (['folder_id' => 'folders', 'client_id' => 'clients'] as $field => $table) {
                $rules[$field] = ['nullable', Rule::exists($table, 'id')->where('organization_id', $organizationId)];
            }
            Validator::make($data, $rules)->validate();
            if (! empty($data['folder_id']) && ! empty($data['client_id'])) {
                Validator::make($data, ['client_id' => [Rule::exists('folder_clients', 'client_id')->where('folder_id', $data['folder_id'])]])->validate();
            }
            $created = 0;
            foreach ($this->preview($rule, $through) as $due) {
                if (! DB::table('payable_recurrence_occurrences')->where('payable_recurrence_id', $rule->id)->where('due_on', $due)->exists()) {
                    $payable = Payable::create(array_replace($data, ['due_on' => $due, 'balance_cents' => $data['amount_cents'], 'paid_cents' => 0, 'status' => 'open']));
                    DB::table('payable_recurrence_occurrences')->insert(['payable_recurrence_id' => $rule->id, 'payable_id' => $payable->id, 'due_on' => $due]);
                    $created++;
                }
                $rule->next_due_on = $this->nextDate($rule, CarbonImmutable::parse($due));
            }
            $rule->last_error = null;
            $rule->save();

            return $created;
        }, 3);
    }
}

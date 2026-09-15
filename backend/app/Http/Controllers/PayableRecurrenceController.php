<?php

namespace App\Http\Controllers;

use App\Http\Requests\PayableRecurrenceRequest;
use App\Models\Payable;
use App\Models\PayableRecurrence;
use App\Services\PayableRecurrenceService;
use Carbon\CarbonImmutable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayableRecurrenceController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(PayableRecurrence::query()->orderByDesc('active')->orderBy('next_due_on')->get());
    }

    public function store(PayableRecurrenceRequest $request, PayableRecurrenceService $service): JsonResponse
    {
        return DB::transaction(function () use ($request, $service): JsonResponse {
            $data = $request->validated();
            $rule = PayableRecurrence::create([
                'template' => collect($data)->except(['due_on', 'interval_months', 'ends_on'])->all(),
                'starts_on' => $data['due_on'], 'next_due_on' => $data['due_on'],
                'interval_months' => $data['interval_months'], 'ends_on' => $data['ends_on'] ?? null,
                'active' => true,
            ]);
            $created = $service->generate($rule, CarbonImmutable::today('America/Sao_Paulo')->addDays(30));

            return response()->json(['recurrence' => $rule->refresh(), 'created' => $created], 201);
        });
    }

    public function update(Request $request, PayableRecurrence $recurrence): JsonResponse
    {
        $data = $request->validate([
            'active' => ['sometimes', 'boolean'],
            'amount_cents' => ['sometimes', 'required', 'integer', 'min:1'],
            'description' => ['sometimes', 'required', 'string', 'max:500'],
        ]);

        return DB::transaction(function () use ($recurrence, $data): JsonResponse {
            $rule = PayableRecurrence::lockForUpdate()->findOrFail($recurrence->id);
            $rule->template = array_replace($rule->template, collect($data)->except('active')->all());
            if (array_key_exists('active', $data)) {
                $rule->active = $data['active'];
            }
            $rule->save();

            return response()->json($rule);
        });
    }

    public function preview(Request $request, PayableRecurrence $recurrence, PayableRecurrenceService $service): JsonResponse
    {
        return response()->json(['dates' => $service->preview($recurrence, $this->through($request)), 'active' => $recurrence->active]);
    }

    public function generate(Request $request, PayableRecurrence $recurrence, PayableRecurrenceService $service): JsonResponse
    {
        return response()->json(['created' => $service->generate($recurrence, $this->through($request)), 'recurrence' => $recurrence->refresh()]);
    }

    public function alerts(): JsonResponse
    {
        $today = today('America/Sao_Paulo')->toDateString();
        $through = today('America/Sao_Paulo')->addDays(7)->toDateString();
        $query = Payable::query()->whereIn('status', ['open', 'partial'])->where('balance_cents', '>', 0);

        return response()->json([
            'due_today' => (clone $query)->whereDate('due_on', $today)->count(),
            'due_soon' => (clone $query)->whereBetween('due_on', [$today, $through])->count(),
            'through' => $through,
        ]);
    }

    private function through(Request $request): CarbonImmutable
    {
        $data = $request->validate(['through' => ['required', 'date_format:Y-m-d', 'after_or_equal:'.today('America/Sao_Paulo')->toDateString(), 'before_or_equal:'.today('America/Sao_Paulo')->addDays(90)->toDateString()]]);

        return CarbonImmutable::parse($data['through']);
    }
}

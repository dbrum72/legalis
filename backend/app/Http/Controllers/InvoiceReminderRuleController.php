<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvoiceReminderRuleRequest;
use App\Models\InvoiceReminderRule;
use Illuminate\Http\JsonResponse;

class InvoiceReminderRuleController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(InvoiceReminderRule::query()->orderBy('days_after_due')->get());
    }

    public function store(InvoiceReminderRuleRequest $request): JsonResponse
    {
        return response()->json(InvoiceReminderRule::query()->create($request->validated()), 201);
    }

    public function update(InvoiceReminderRuleRequest $request, InvoiceReminderRule $reminderRule): JsonResponse
    {
        $reminderRule->update($request->validated());

        return response()->json($reminderRule->refresh());
    }

    public function destroy(InvoiceReminderRule $reminderRule): JsonResponse
    {
        $reminderRule->delete();

        return response()->json(null, 204);
    }
}

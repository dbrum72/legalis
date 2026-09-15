<?php

namespace App\Http\Controllers;

use App\Models\FinancialClassification;
use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class FinancialClassificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        abort_unless($request->user('api')->can('finance.view') || $request->user('api')->can('expenses.view'), 403);

        return response()->json(FinancialClassification::query()->orderBy('kind')->orderBy('name')->get());
    }

    public function store(Request $request, CurrentOrganization $organization): JsonResponse
    {
        if (is_string($request->input('name'))) {
            $request->merge(['name' => trim($request->input('name'))]);
        }
        $data = $request->validate([
            'kind' => ['required', Rule::in(['category', 'cost_center'])],
            'name' => ['required', 'string', 'max:80', Rule::unique('financial_classifications')->where('organization_id', $organization->id())->where('kind', $request->input('kind'))],
        ]);

        return response()->json(FinancialClassification::create($data + ['active' => true]), 201);
    }

    public function update(Request $request, FinancialClassification $classification, CurrentOrganization $organization): JsonResponse
    {
        if (is_string($request->input('name'))) {
            $request->merge(['name' => trim($request->input('name'))]);
        }
        $data = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:80', Rule::unique('financial_classifications')->where('organization_id', $organization->id())->where('kind', $classification->kind)->ignore($classification->id)],
            'active' => ['sometimes', 'boolean'],
        ]);
        $classification->update($data);

        return response()->json($classification->refresh());
    }
}

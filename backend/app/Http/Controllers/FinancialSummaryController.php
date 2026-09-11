<?php

namespace App\Http\Controllers;

use App\Services\FinancialSummaryService;
use Illuminate\Http\JsonResponse;

class FinancialSummaryController extends Controller
{
    public function __invoke(FinancialSummaryService $summary): JsonResponse
    {
        return response()->json($summary->get())
            ->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }
}

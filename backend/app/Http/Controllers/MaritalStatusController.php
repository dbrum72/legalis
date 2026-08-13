<?php

namespace App\Http\Controllers;

use App\Models\MaritalStatus;
use Illuminate\Http\JsonResponse;

class MaritalStatusController extends Controller
{
    public function index(): JsonResponse
    {
        $maritalStatuses = MaritalStatus::query()
            ->orderBy('id')
            ->get([
                'id',
                'name',
            ]);

        return response()->json($maritalStatuses);
    }
}
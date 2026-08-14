<?php

namespace App\Http\Controllers;

use App\Models\Qualification;
use Illuminate\Http\JsonResponse;

class QualificationController extends Controller
{
    public function index(): JsonResponse
    {
        return response()->json(
            Qualification::query()
                ->orderBy('name')
                ->get([
                    'id',
                    'name',
                ])
        );
    }
}

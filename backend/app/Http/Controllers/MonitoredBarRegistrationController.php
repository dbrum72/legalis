<?php

namespace App\Http\Controllers;

use App\Http\Requests\MonitoredBarRegistrationRequest;
use App\Models\MonitoredBarRegistration;
use Illuminate\Http\JsonResponse;

class MonitoredBarRegistrationController extends Controller
{
    public function index(): JsonResponse
    {
        $registrations =
            MonitoredBarRegistration::query()
                ->withCount(
                    'publications'
                )
                ->orderBy(
                    'lawyer_name'
                )
                ->orderBy(
                    'state'
                )
                ->orderBy(
                    'bar_number'
                )
                ->get();

        return response()->json(
            $registrations
        );
    }

    public function store(
        MonitoredBarRegistrationRequest $request,
    ): JsonResponse {
        $data =
            $request->validated();

        $data['active'] =
            $data['active']
            ?? true;

        $registration =
            MonitoredBarRegistration::query()
                ->create(
                    $data
                );

        return response()->json(
            $registration,
            201,
        );
    }

    public function update(
        MonitoredBarRegistrationRequest $request,
        MonitoredBarRegistration $monitoredBarRegistration,
    ): JsonResponse {
        $monitoredBarRegistration->update(
            $request->validated()
        );

        return response()->json(
            $monitoredBarRegistration->refresh()
        );
    }
}

<?php

namespace App\Http\Controllers;

use App\Http\Requests\FeeAgreementRequest;
use App\Models\FeeAgreement;
use App\Models\Folder;
use Illuminate\Http\JsonResponse;
use Illuminate\Validation\ValidationException;

class FeeAgreementController extends Controller
{
    public function index(Folder $folder): JsonResponse
    {
        return response()->json($folder->feeAgreements()->with('client:id,name')->latest()->get());
    }

    public function store(FeeAgreementRequest $request, Folder $folder): JsonResponse
    {
        $data = $request->validated();
        $this->ensureClientBelongsToFolder($folder, $data['client_id'] ?? null);
        $this->validateTerms($data);
        $agreement = $folder->feeAgreements()->create($data + ['status' => $data['status'] ?? 'draft']);

        return response()->json($agreement->load('client:id,name'), 201);
    }

    public function update(FeeAgreementRequest $request, Folder $folder, FeeAgreement $feeAgreement): JsonResponse
    {
        $this->ensureBelongsToFolder($folder, $feeAgreement);
        $data = $request->validated();
        $this->ensureClientBelongsToFolder($folder, $data['client_id'] ?? $feeAgreement->client_id);
        $this->validateTerms(array_merge($feeAgreement->toArray(), $data));
        $feeAgreement->update($data);

        return response()->json($feeAgreement->refresh()->load('client:id,name'));
    }

    public function destroy(Folder $folder, FeeAgreement $feeAgreement): JsonResponse
    {
        $this->ensureBelongsToFolder($folder, $feeAgreement);
        if ($feeAgreement->invoices()->exists()) {
            throw ValidationException::withMessages(['fee_agreement' => 'O contrato possui cobranças vinculadas.']);
        }
        $feeAgreement->delete();

        return response()->json(null, 204);
    }

    private function ensureBelongsToFolder(Folder $folder, FeeAgreement $agreement): void
    {
        abort_unless((int) $agreement->folder_id === (int) $folder->id, 404);
    }

    private function ensureClientBelongsToFolder(Folder $folder, ?int $clientId): void
    {
        if ($clientId !== null && ! $folder->clients()->whereKey($clientId)->exists()) {
            throw ValidationException::withMessages(['client_id' => 'O cliente deve estar vinculado à pasta.']);
        }
    }

    private function validateTerms(array $data): void
    {
        $fields = [
            'hourly' => 'hourly_rate_cents',
            'fixed' => 'fixed_fee_cents',
            'contingency' => 'contingency_percentage',
        ];
        $type = $data['type'];

        if (isset($fields[$type]) && empty($data[$fields[$type]])) {
            throw ValidationException::withMessages([
                $fields[$type] => 'Informe a condição financeira correspondente ao tipo do contrato.',
            ]);
        }

        if ($type === 'hybrid') {
            $termCount = collect($fields)->filter(fn (string $field): bool => ! empty($data[$field]))->count();
            if ($termCount < 2) {
                throw ValidationException::withMessages([
                    'type' => 'O contrato híbrido deve combinar ao menos duas formas de honorários.',
                ]);
            }
        }
    }
}

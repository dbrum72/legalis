<?php

namespace App\Http\Controllers;

use App\Models\Organization;
use App\Models\Payment;
use App\Models\PayablePayment;
use App\Services\FinancialAudit;
use App\Services\FinancialReconciliationService;
use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class FinancialReconciliationController extends Controller
{
    private function month(Request $request): string
    {
        return $request->validate(['month' => ['required', 'date_format:Y-m', 'after_or_equal:1900-01', 'before:9999-01']])['month'];
    }

    public function index(Request $request, FinancialReconciliationService $service, CurrentOrganization $organization): JsonResponse
    {
        $month = $this->month($request);
        $params = $request->validate(['page' => ['sometimes', 'integer', 'min:1'], 'audit_page' => ['sometimes', 'integer', 'min:1'], 'status' => ['nullable', Rule::in(['pending', 'divergent', 'reconciled', 'cancelled'])]]);
        $report = $service->report($month);
        unset($report['snapshot']);
        $rows = collect($report['rows'])->when($request->filled('status'), fn ($rows) => $rows->where('status', $params['status']))->sortBy(fn ($r) => $r['source']['paid_at'].$r['key'])->values();
        $report['total'] = $rows->count();
        $report['page'] = $params['page'] ?? 1;
        $report['rows'] = $rows->forPage($report['page'], 25)->values();
        if ($report['closing']) {
            $snapshot = json_decode($report['closing']->snapshot, true);
            $report['closing']->totals = $snapshot['summary'];
            unset($report['closing']->snapshot);
        }
        $events = DB::table('financial_audit_events')->where('organization_id', $organization->id())->where('month', $month)->orderByDesc('id');
        $report['audit_total'] = (clone $events)->count();
        $report['audit_page'] = $params['audit_page'] ?? 1;
        $report['audit'] = $events->forPage($report['audit_page'], 25)->get()->map(function ($event) {
            $event->before = $event->before ? json_decode($event->before, true) : null;
            $event->after = $event->after ? json_decode($event->after, true) : null;
            return $event;
        });

        return response()->json($report);
    }

    public function check(Request $request, string $kind, int $id, CurrentOrganization $organization): JsonResponse
    {
        abort_unless(in_array($kind, ['incoming', 'outgoing'], true), 404);
        $data = $request->validate([
            'observed_amount_cents' => ['required', 'integer', 'min:0', 'max:9007199254740991'],
            'observed_on' => ['required', 'date_format:Y-m-d'],
            'reference' => ['required', 'string', 'max:180'],
            'note' => ['required', 'string', 'max:2000'],
            'source_hash' => ['required', 'string', 'size:64'],
        ]);

        return DB::transaction(function () use ($request, $kind, $id, $organization, $data): JsonResponse {
            Organization::whereKey($organization->id())->lockForUpdate()->firstOrFail();
            $model = $kind === 'incoming' ? Payment::class : PayablePayment::class;
            $payment = $model::lockForUpdate()->findOrFail($id);
            abort_if($payment->cancelled_at, 409, 'O pagamento foi cancelado. Atualize a consulta.');
            $source = FinancialAudit::source($payment);
            abort_unless(hash_equals(FinancialAudit::hash($source), $data['source_hash']), 409, 'O pagamento mudou. Atualize a consulta antes de conferir.');
            $key = $kind === 'incoming' ? 'payment_id' : 'payable_payment_id';
            $query = DB::table('financial_reconciliations')->where('organization_id', $organization->id())->where($key, $id);
            $before = $query->first();
            $values = $data + ['organization_id' => $organization->id(), $key => $id, 'checked_by' => $request->user('api')->id, 'checked_at' => now(), 'updated_at' => now()];
            if ($before) {
                $query->update($values);
            } else {
                DB::table('financial_reconciliations')->insert($values + ['created_at' => now()]);
            }
            $after = (array) $query->first();
            FinancialAudit::record($organization->id(), substr($source['paid_at'], 0, 7), $before ? 'reconciliation.updated' : 'reconciliation.created', $source['key'], $before ? (array) $before : null, $after, $data['note']);

            return response()->json($after);
        }, 3);
    }

    public function close(Request $request, FinancialReconciliationService $service, CurrentOrganization $organization): JsonResponse
    {
        $month = $this->month($request);
        $data = $request->validate(['fingerprint' => ['required', 'string', 'size:64'], 'note' => ['required', 'string', 'max:2000']]);

        return DB::transaction(function () use ($request, $service, $organization, $month, $data): JsonResponse {
            Organization::whereKey($organization->id())->lockForUpdate()->firstOrFail();
            $report = $service->report($month);
            abort_unless(hash_equals($report['fingerprint'], $data['fingerprint']), 409, 'O período mudou. Atualize a consulta antes de fechar.');
            abort_unless($report['can_close'], 422, 'Feche apenas meses encerrados, abertos e sem conferências pendentes ou divergentes.');
            $snapshot = ['summary' => $report['summary'], 'rows' => $report['snapshot']];
            DB::table('financial_closings')->updateOrInsert(['organization_id' => $organization->id(), 'month' => $month], [
                'snapshot' => json_encode($snapshot, JSON_THROW_ON_ERROR), 'fingerprint' => $report['fingerprint'],
                'closed_by' => $request->user('api')->id, 'closed_at' => now(), 'note' => $data['note'], 'updated_at' => now(), 'created_at' => $report['closing']?->created_at ?? now(),
            ]);
            FinancialAudit::record($organization->id(), $month, 'period.closed', $month, null, $snapshot, $data['note']);

            return response()->json(['message' => 'Período fechado. Alterações posteriores serão sinalizadas para revisão.']);
        }, 3);
    }

    public function reopen(Request $request, CurrentOrganization $organization): JsonResponse
    {
        $month = $this->month($request);
        $data = $request->validate(['note' => ['required', 'string', 'max:2000']]);

        return DB::transaction(function () use ($organization, $month, $data): JsonResponse {
            Organization::whereKey($organization->id())->lockForUpdate()->firstOrFail();
            $query = DB::table('financial_closings')->where('organization_id', $organization->id())->where('month', $month);
            $closing = $query->first();
            abort_unless($closing?->closed_at, 422, 'O período já está aberto.');
            $query->update(['closed_at' => null, 'updated_at' => now()]);
            FinancialAudit::record($organization->id(), $month, 'period.reopened', $month, ['closed_at' => $closing->closed_at, 'fingerprint' => $closing->fingerprint], null, $data['note']);

            return response()->json(['message' => 'Período reaberto para conferência.']);
        }, 3);
    }
}

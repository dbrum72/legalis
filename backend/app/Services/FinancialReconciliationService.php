<?php

namespace App\Services;

use App\Models\Payment;
use App\Models\PayablePayment;
use App\Support\Tenancy\CurrentOrganization;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

class FinancialReconciliationService
{
    public function report(string $month): array
    {
        $organization = app(CurrentOrganization::class)->id();
        $start = CarbonImmutable::createFromFormat('!Y-m', $month);
        $end = $start->addMonth();
        $payments = Payment::with('invoice.client:id,name')->where('paid_at', '>=', $start)->where('paid_at', '<', $end)->get();
        $outgoing = PayablePayment::with('payable:id,supplier,description')->where('paid_at', '>=', $start)->where('paid_at', '<', $end)->get();
        $checks = DB::table('financial_reconciliations')->where('organization_id', $organization)
            ->where(fn ($q) => $q->whereIn('payment_id', $payments->modelKeys())->orWhereIn('payable_payment_id', $outgoing->modelKeys()))
            ->get()->keyBy(fn ($r) => $r->payment_id ? 'incoming:'.$r->payment_id : 'outgoing:'.$r->payable_payment_id);
        $summary = ['incoming_cents' => 0, 'outgoing_cents' => 0, 'pending' => 0, 'divergent' => 0, 'reconciled' => 0, 'cancelled' => 0];
        $rows = [];
        foreach ($payments->concat($outgoing) as $payment) {
            $source = FinancialAudit::source($payment);
            $kind = $payment instanceof PayablePayment ? 'outgoing' : 'incoming';
            $check = $checks->get($source['key']);
            $status = 'pending';
            if ($payment->cancelled_at) {
                $status = 'cancelled';
            } elseif ($check?->checked_at && $check->source_hash === FinancialAudit::hash($source)) {
                $status = (int) $check->observed_amount_cents === (int) $payment->amount_cents && $check->observed_on === substr($source['paid_at'], 0, 10) ? 'reconciled' : 'divergent';
            }
            $summary[$status]++;
            if ($status !== 'cancelled') {
                $summary[$kind.'_cents'] += (int) $payment->amount_cents;
            }
            $rows[] = [
                'key' => $source['key'], 'kind' => $kind, 'id' => $payment->id,
                'party' => $kind === 'incoming' ? $payment->invoice?->client?->name : $payment->payable?->supplier,
                'description' => $kind === 'incoming' ? 'Recebimento da cobrança #'.$payment->invoice_id : $payment->payable?->description,
                'source' => $source, 'source_hash' => FinancialAudit::hash($source), 'status' => $status, 'check' => $check ? (array) $check : null,
                'difference_cents' => $check ? (int) $check->observed_amount_cents - (int) $payment->amount_cents : null,
            ];
        }
        usort($rows, fn ($a, $b) => strcmp($a['key'], $b['key']));
        $snapshot = array_map(fn ($r) => ['source' => $r['source'], 'check' => $r['check']], $rows);
        $fingerprint = FinancialAudit::hash($snapshot);
        $closing = DB::table('financial_closings')->where('organization_id', $organization)->where('month', $month)->first();
        $state = ! $closing?->closed_at ? 'open' : ($closing->fingerprint === $fingerprint ? 'closed' : 'review_required');
        $canClose = $summary['pending'] === 0 && $summary['divergent'] === 0 && $month < today('America/Sao_Paulo')->format('Y-m') && $state === 'open';
        $summary['net_cents'] = $summary['incoming_cents'] - $summary['outgoing_cents'];

        return ['month' => $month, 'rows' => $rows, 'summary' => $summary, 'fingerprint' => $fingerprint, 'snapshot' => $snapshot, 'state' => $state, 'closing' => $closing, 'can_close' => $canClose];
    }
}

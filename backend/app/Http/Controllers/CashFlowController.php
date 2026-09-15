<?php

namespace App\Http\Controllers;

use App\Http\Requests\CashFlowRequest;
use App\Services\CashFlowService;
use App\Support\Tenancy\CurrentOrganization;
use Illuminate\Http\JsonResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CashFlowController extends Controller
{
    public function index(CashFlowRequest $request, CashFlowService $service): JsonResponse
    {
        $report = $service->report($request->period());
        $total = count($report['entries']);
        $lastPage = max(1, (int) ceil($total / 25));
        $page = min($request->integer('page', 1), $lastPage);
        $report['entries'] = array_slice($report['entries'], ($page - 1) * 25, 25);
        $report['pagination'] = ['current_page' => $page, 'last_page' => $lastPage, 'per_page' => 25, 'total' => $total];

        return response()->json($report)->header('Cache-Control', 'no-store');
    }

    public function export(CashFlowRequest $request, CashFlowService $service, CurrentOrganization $organization): StreamedResponse
    {
        $report = $service->report($request->period());
        $organizationName = $organization->get()->name;

        return response()->streamDownload(function () use ($report, $organizationName): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            $write = function (array $row) use ($output): void {
                fputcsv($output, array_map(fn ($value) => is_string($value) && ! is_numeric(str_replace(',', '.', $value)) && preg_match('/^[\s]*[=+@-]/u', $value) ? "'".$value : $value, $row), ';', '"', '');
            };
            $write([$organizationName, 'Fluxo de caixa']);
            $write(['De', $report['period']['from'], 'Até', $report['period']['to']]);
            $write(['Posição consultada em', $report['as_of']]);
            $write(['Projeção: saldos pendentes atuais por vencimento; não representa saldo histórico nem garantia de recebimento.']);
            $write(['Movimentação do período, sem saldo inicial bancário.']);
            $write(['Situação', 'Entradas (R$)', 'Saídas (R$)', 'Resultado (R$)']);
            foreach (['realized' => 'Realizado', 'projected' => 'Pendente'] as $key => $label) {
                $write([$label, $this->money($report[$key]['in_cents']), $this->money($report[$key]['out_cents']), $this->money($report[$key]['net_cents'])]);
            }
            $write([]);
            $write(['Margem de caixa realizada (%)', $report['indicators']['cash_margin_percent'] ?? 'Sem entradas']);
            $write(['Inadimplência: saldo vencido atual / cobranças do período (%)', $report['indicators']['overdue_percent'] ?? 'Sem cobranças']);
            $write(['Maior cliente', $report['indicators']['largest_client']['name'] ?? 'Sem recebimentos', 'Participação (%)', $report['indicators']['largest_client_percent'] ?? '']);
            $write(['Mês', 'Entradas realizadas (R$)', 'Saídas realizadas (R$)', 'Resultado realizado (R$)', 'Resultado pendente (R$)']);
            foreach ($report['monthly'] as $month) {
                $write([$month['month'], $this->money($month['realized']['in_cents']), $this->money($month['realized']['out_cents']), $this->money($month['realized']['net_cents']), $this->money($month['projected']['net_cents'])]);
            }
            $write([]);
            $write(['Data', 'Situação', 'Direção', 'Cliente / fornecedor', 'Descrição', 'Referência', 'Forma', 'Valor (R$)']);
            foreach ($report['entries'] as $entry) {
                $write([$entry['date'], $entry['stage'] === 'realized' ? 'Realizado' : 'Pendente', $entry['direction'] === 'in' ? 'Entrada' : 'Saída',
                    $entry['party'], $entry['description'], $entry['reference'] ?? '', $entry['method'] ?? '', $this->money($entry['amount_cents'])]);
            }
            fclose($output);
        }, 'fluxo-de-caixa-'.$report['period']['from'].'-'.$report['period']['to'].'.csv', ['Content-Type' => 'text/csv; charset=UTF-8', 'Cache-Control' => 'no-store']);
    }

    private function money(int $cents): string
    {
        return number_format($cents / 100, 2, ',', '');
    }
}

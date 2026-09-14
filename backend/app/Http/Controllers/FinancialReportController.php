<?php

namespace App\Http\Controllers;

use App\Services\FinancialSummaryService;
use App\Support\Tenancy\CurrentOrganization;
use Symfony\Component\HttpFoundation\StreamedResponse;

class FinancialReportController extends Controller
{
    public function __invoke(FinancialSummaryService $summaryService, CurrentOrganization $currentOrganization): StreamedResponse
    {
        $summary = $summaryService->get();
        $organization = $currentOrganization->get();
        $filename = 'relatorio-financeiro-'.today()->format('Y-m-d').'.csv';

        return response()->streamDownload(function () use ($summary, $organization): void {
            $output = fopen('php://output', 'w');
            fwrite($output, "\xEF\xBB\xBF");
            fputcsv($output, [$organization->name, 'Relatório financeiro'], ';');
            fputcsv($output, ['Gerado em', now()->format('d/m/Y H:i')], ';');
            fputcsv($output, [], ';');
            fputcsv($output, ['RESUMO EXECUTIVO'], ';');
            fputcsv($output, ['A receber (R$)', $this->money($summary['receivable_cents'])], ';');
            fputcsv($output, ['Vencido (R$)', $this->money($summary['overdue_cents'])], ';');
            fputcsv($output, ['Cobranças vencidas', $summary['overdue_count']], ';');
            fputcsv($output, ['Recebido no mês (R$)', $this->money($summary['received_this_month_cents'])], ';');
            fputcsv($output, ['Previsão próximos 90 dias (R$)', $this->money($summary['forecast_90_days_cents'])], ';');
            fputcsv($output, [], ';');
            fputcsv($output, ['DESEMPENHO MENSAL'], ';');
            fputcsv($output, ['Competência', 'Faturado (R$)', 'Recebido (R$)', 'Despesas pagas (R$)', 'Resultado de caixa (R$)'], ';');
            foreach ($summary['monthly_performance'] as $month) {
                fputcsv($output, [$month['month'], $this->money($month['billed_cents']), $this->money($month['received_cents']), $this->money($month['paid_expenses_cents']), $this->money($month['received_cents'] - $month['paid_expenses_cents'])], ';');
            }
            fputcsv($output, [], ';');
            fputcsv($output, ['PREVISÃO DE CAIXA'], ';');
            fputcsv($output, ['Faixa', 'Parcelas', 'Saldo (R$)'], ';');
            $labels = ['overdue' => 'Vencido', 'next_30_days' => 'Até 30 dias', 'days_31_60' => '31–60 dias', 'days_61_90' => '61–90 dias', 'after_90_days' => 'Após 90 dias'];
            foreach ($labels as $key => $label) {
                fputcsv($output, [$label, $summary['forecast'][$key]['count'], $this->money($summary['forecast'][$key]['balance_cents'])], ';');
            }
            fputcsv($output, [], ';');
            fputcsv($output, ['INADIMPLÊNCIA POR CLIENTE'], ';');
            fputcsv($output, ['Cliente', 'E-mail', 'Parcelas', 'Saldo vencido (R$)', 'Vencimento mais antigo', 'Último contato'], ';');
            foreach ($summary['overdue_clients'] as $client) {
                fputcsv($output, [$client['client_name'], $client['client_email'] ?? '', $client['invoice_count'], $this->money($client['balance_cents']), $client['oldest_due_on'] ?? '', $client['last_reminder_at'] ?? ''], ';');
            }
            fclose($output);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function money(int $cents): string
    {
        return number_format($cents / 100, 2, ',', '.');
    }
}

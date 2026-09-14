<!doctype html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Comprovante {{ $payment->invoice->charge_identifier }}</title>
    <style>
        :root{color-scheme:light;--earth:#4b3428;--moss:#466447;--orange:#ed8b3a;--paper:#fffdf9;--line:#e4d8cc;--muted:#796d64}*{box-sizing:border-box}body{margin:0;padding:32px;color:var(--earth);background:#f4efe8;font:14px/1.5 Arial,sans-serif}.receipt{max-width:760px;margin:auto;overflow:hidden;background:var(--paper);border:1px solid var(--line);border-radius:18px;box-shadow:0 18px 48px rgba(61,43,31,.12)}header{display:flex;padding:28px 32px;align-items:center;justify-content:space-between;gap:24px;color:#fff;background:linear-gradient(125deg,var(--earth),var(--moss))}.brand strong{display:block;font-size:22px}.brand span,.document-id span{color:rgba(255,255,255,.72);font-size:11px;letter-spacing:.08em;text-transform:uppercase}.document-id{text-align:right}.document-id strong{display:block;margin-top:4px;font-size:16px}.content{padding:32px}.confirmation{display:flex;padding:18px 20px;align-items:center;justify-content:space-between;gap:20px;background:#edf3e9;border-left:4px solid var(--moss);border-radius:10px}.confirmation span{color:var(--muted)}.confirmation strong{font-size:28px;color:var(--moss)}dl{display:grid;grid-template-columns:1fr 1fr;margin:28px 0;overflow:hidden;border:1px solid var(--line);border-radius:12px}dl div{padding:15px 18px;border-right:1px solid var(--line);border-bottom:1px solid var(--line)}dl div:nth-child(2n){border-right:0}dl div:nth-last-child(-n+2){border-bottom:0}dt{color:var(--muted);font-size:11px;text-transform:uppercase}dd{margin:4px 0 0;font-weight:700}.notes{padding:16px 18px;background:#fff0e4;border-left:3px solid var(--orange);border-radius:9px}.notes strong{display:block;margin-bottom:4px}.footer{display:flex;padding-top:22px;align-items:flex-end;justify-content:space-between;gap:20px;color:var(--muted);border-top:1px solid var(--line);font-size:12px}.actions{max-width:760px;margin:18px auto;text-align:right}.actions button{padding:11px 18px;color:#fff;background:var(--orange);border:0;border-radius:9px;font-weight:700;cursor:pointer}@media(max-width:600px){body{padding:0}.receipt{border:0;border-radius:0}header,.content{padding:22px}dl{grid-template-columns:1fr}dl div,dl div:nth-child(2n),dl div:nth-last-child(-n+2){border-right:0;border-bottom:1px solid var(--line)}dl div:last-child{border-bottom:0}.confirmation,.footer{align-items:flex-start;flex-direction:column}.actions{padding:0 16px}}@media print{body{padding:0;background:#fff}.receipt{max-width:none;border:0;box-shadow:none}.actions{display:none}}
    </style>
</head>
<body>
<main class="receipt">
    <header><div class="brand"><span>Escritório responsável</span><strong>{{ $payment->invoice->organization->name }}</strong></div><div class="document-id"><span>Comprovante de recebimento</span><strong>#{{ str_pad((string) $payment->id, 6, '0', STR_PAD_LEFT) }}</strong></div></header>
    <div class="content">
        <section class="confirmation"><span>Valor recebido e registrado</span><strong>R$ {{ number_format($payment->amount_cents / 100, 2, ',', '.') }}</strong></section>
        <dl>
            <div><dt>Cliente</dt><dd>{{ $payment->invoice->client->name }}</dd></div><div><dt>Documento</dt><dd>{{ $payment->invoice->client->document ?: 'Não informado' }}</dd></div>
            <div><dt>Cobrança</dt><dd>{{ $payment->invoice->charge_identifier }}</dd></div><div><dt>Parcela</dt><dd>{{ $payment->invoice->installment_number }}/{{ $payment->invoice->installment_count }}</dd></div>
            <div><dt>Data do pagamento</dt><dd>{{ $payment->paid_at->format('d/m/Y H:i') }}</dd></div><div><dt>Forma</dt><dd>{{ ['pix'=>'Pix','bank_transfer'=>'Transferência bancária','cash'=>'Dinheiro','credit_card'=>'Cartão de crédito','debit_card'=>'Cartão de débito','boleto'=>'Boleto','other'=>'Outra'][$payment->method] ?? $payment->method }}</dd></div>
            <div><dt>Referência</dt><dd>{{ $payment->reference ?: 'Não informada' }}</dd></div><div><dt>Pasta</dt><dd>{{ $payment->invoice->folder?->name ?: 'Sem pasta' }}</dd></div>
        </dl>
        @if($payment->notes)<section class="notes"><strong>Observações</strong>{{ $payment->notes }}</section>@endif
        <footer class="footer"><span>Registrado por {{ $payment->recordedBy?->name ?? 'Sistema' }}</span><span>Emitido em {{ now()->format('d/m/Y H:i') }}</span></footer>
    </div>
</main>
<div class="actions"><button type="button" onclick="window.print()">Imprimir ou salvar em PDF</button></div>
</body>
</html>

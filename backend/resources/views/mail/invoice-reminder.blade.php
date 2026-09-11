<!doctype html>
<html lang="pt-BR">
<body style="margin:0;background:#f7f4ee;color:#35251b;font-family:Arial,Helvetica,sans-serif">
    <div style="display:none;max-height:0;overflow:hidden;color:transparent">Lembrete referente à cobrança {{ $invoice->charge_identifier }}.</div>
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f7f4ee">
        <tr><td align="center" style="padding:32px 16px">
            <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:620px;background:#ffffff;border:1px solid #ded5c8;border-radius:14px;overflow:hidden">
                <tr><td style="padding:22px 28px;background:#526b4e;color:#ffffff">
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0"><tr>
                        <td width="48" valign="middle"><div style="width:42px;height:42px;line-height:42px;text-align:center;background:#ffffff;color:#526b4e;border-radius:50%;font-size:15px;font-weight:700">{{ collect(explode(' ', $invoice->organization->name))->filter()->take(2)->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->join('') }}</div></td>
                        <td valign="middle" style="padding-left:12px"><div style="font-size:18px;font-weight:700;line-height:1.25">{{ $invoice->organization->name }}</div><div style="padding-top:3px;color:#e4eddf;font-size:12px;letter-spacing:.06em;text-transform:uppercase">Comunicado financeiro</div></td>
                    </tr></table>
                </td></tr>
                <tr><td style="padding:28px">
                    <p style="margin:0 0 22px;color:#49352a;font-size:15px;line-height:1.65;white-space:pre-line">{{ $mailMessage }}</p>
                    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#edf3e9;border-left:4px solid #526b4e;border-radius:8px">
                        <tr><td style="padding:17px 18px">
                            <div style="color:#526b4e;font-size:12px;font-weight:700;letter-spacing:.05em;text-transform:uppercase">Resumo da cobrança</div>
                            <div style="padding-top:8px;color:#35251b;font-size:16px;font-weight:700">{{ $invoice->charge_identifier }}</div>
                            <div style="padding-top:5px;color:#75665b;font-size:13px;line-height:1.5">Parcela {{ $invoice->installment_number }}/{{ $invoice->installment_count }} &nbsp;·&nbsp; Vencimento {{ $invoice->due_on?->format('d/m/Y') }}</div>
                            <div style="padding-top:12px;color:#526b4e;font-size:19px;font-weight:700">Saldo: R$ {{ number_format($invoice->balance_cents / 100, 2, ',', '.') }}</div>
                        </td></tr>
                    </table>
                </td></tr>
                <tr><td style="padding:18px 28px;background:#fbfaf7;border-top:1px solid #eee8df;color:#8a7b70;font-size:12px;line-height:1.5">Mensagem enviada por <strong style="color:#5f4a3e">{{ $invoice->organization->name }}</strong> através do Legalis.</td></tr>
            </table>
        </td></tr>
    </table>
</body>
</html>

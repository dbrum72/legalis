<?php

namespace App\Http\Controllers;

use App\Http\Requests\InvoiceReminderRequest;
use App\Mail\InvoiceReminderMail;
use App\Models\Invoice;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;

class InvoiceReminderController extends Controller
{
    public function store(InvoiceReminderRequest $request, Invoice $invoice): JsonResponse
    {
        if (! in_array($invoice->status, ['open', 'partial'], true) || $invoice->balance_cents <= 0) {
            throw ValidationException::withMessages(['invoice' => 'Somente cobranças pendentes podem receber lembretes.']);
        }

        $invoice->loadMissing(['client:id,name,email', 'organization:id,name']);
        if (! $invoice->client?->email) {
            throw ValidationException::withMessages(['recipient' => 'O cliente não possui e-mail cadastrado.']);
        }

        $data = $request->validated();
        Mail::to($invoice->client->email)->send(new InvoiceReminderMail($invoice, $data['subject'], $data['message']));
        $reminder = $invoice->reminders()->create([
            'sent_by' => $request->user('api')?->id,
            'channel' => 'email',
            'recipient' => $invoice->client->email,
            'subject' => $data['subject'],
            'message' => $data['message'],
            'sent_at' => now(),
        ]);

        return response()->json($reminder->load('sentBy:id,name'), 201);
    }
}

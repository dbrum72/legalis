<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class PayablePaymentRequest extends FormRequest { public function rules(): array { return ['paid_at'=>['required','date'],'amount_cents'=>['required','integer','min:1'],'method'=>['required','string','in:pix,bank_transfer,cash,credit_card,debit_card,boleto,other'],'reference'=>['nullable','string','max:120']]; } }

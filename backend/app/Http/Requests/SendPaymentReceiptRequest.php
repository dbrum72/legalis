<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendPaymentReceiptRequest extends FormRequest
{
    public function rules(): array { return ['recipient' => ['required', 'email:rfc', 'max:255']]; }
}

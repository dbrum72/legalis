<?php
namespace App\Http\Requests;
use Illuminate\Foundation\Http\FormRequest;
class PayableRequest extends FormRequest { public function rules(): array { return ['supplier'=>['required','string','max:180'],'description'=>['required','string','max:500'],'category'=>['nullable','string','max:80'],'due_on'=>['required','date'],'amount_cents'=>['required','integer','min:1'],'notes'=>['nullable','string','max:10000']]; } }

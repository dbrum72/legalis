<?php
namespace App\Http\Controllers;
use App\Http\Requests\PayablePaymentRequest;
use App\Http\Requests\CancelPaymentRequest;
use App\Models\PayablePayment;
use App\Models\Payable;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
class PayablePaymentController extends Controller { public function store(PayablePaymentRequest $request, Payable $payable): JsonResponse { $payment=DB::transaction(function()use($request,$payable){$locked=Payable::query()->lockForUpdate()->findOrFail($payable->id);$amount=$request->integer('amount_cents');if(!in_array($locked->status,['open','partial'],true)||$amount>$locked->balance_cents)throw ValidationException::withMessages(['amount_cents'=>'O pagamento deve respeitar o saldo em aberto.']);$payment=$locked->payments()->create($request->validated()+['recorded_by'=>$request->user('api')?->id]);$paid=(int)$locked->payments()->whereNull('cancelled_at')->sum('amount_cents');$balance=$locked->amount_cents-$paid;$locked->update(['paid_cents'=>$paid,'balance_cents'=>$balance,'status'=>$balance===0?'paid':'partial']);return $payment;});return response()->json($payment->load('recordedBy:id,name'),201); }
 public function cancel(CancelPaymentRequest $request,Payable $payable,PayablePayment $payment):JsonResponse{abort_unless($payment->payable_id===$payable->id,404);DB::transaction(function()use($request,$payable,$payment){$p=PayablePayment::query()->lockForUpdate()->findOrFail($payment->id);abort_if($p->cancelled_at,422,'Pagamento já cancelado.');$p->update(['cancelled_by'=>$request->user('api')?->id,'cancellation_reason'=>$request->string('reason')->trim(),'cancelled_at'=>now()]);$paid=(int)$payable->payments()->whereNull('cancelled_at')->sum('amount_cents');$payable->update(['paid_cents'=>$paid,'balance_cents'=>$payable->amount_cents-$paid,'status'=>$paid?'partial':'open']);});return response()->json($payment->refresh());} }

<?php
namespace App\Http\Controllers;
use App\Http\Requests\PayableRequest;
use App\Models\Payable;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use App\Http\Requests\CancelPaymentRequest;
class PayableController extends Controller {
 public function index(Request $request): JsonResponse { $q=Payable::query()->with(['payments.recordedBy:id,name','payments.cancelledBy:id,name']); if($request->filled('supplier'))$q->where('supplier','like','%'.$request->string('supplier')->trim().'%'); if($request->filled('status')){if($request->status==='overdue')$q->whereIn('status',['open','partial'])->whereDate('due_on','<',today());else $q->where('status',$request->status);} if($request->filled('due_from'))$q->whereDate('due_on','>=',$request->date('due_from')); if($request->filled('due_to'))$q->whereDate('due_on','<=',$request->date('due_to')); return response()->json($q->orderByRaw("CASE WHEN status IN ('open','partial') AND due_on < CURRENT_DATE THEN 0 ELSE 1 END")->orderBy('due_on')->get()); }
 public function store(PayableRequest $request): JsonResponse { $data=$request->validated(); return response()->json(Payable::query()->create($data+['balance_cents'=>$data['amount_cents'],'status'=>'open']),201); }
 public function update(PayableRequest $request, Payable $payable): JsonResponse { abort_if($payable->paid_cents>0,422,'Contas com pagamentos não podem ser alteradas.'); $data=$request->validated(); $payable->update($data+['balance_cents'=>$data['amount_cents']]); return response()->json($payable->refresh()); }
 public function destroy(Payable $payable): JsonResponse { abort_if($payable->payments()->exists(),422,'Contas com pagamentos não podem ser excluídas.'); $payable->delete(); return response()->json(null,204); }
 public function cancel(CancelPaymentRequest $request, Payable $payable): JsonResponse { abort_if($payable->payments()->whereNull('cancelled_at')->exists(),422,'Uma conta com pagamentos ativos não pode ser cancelada.'); $payable->update(['status'=>'cancelled','balance_cents'=>0,'cancellation_reason'=>$request->string('reason')->trim()->toString(),'cancelled_at'=>now()]); return response()->json($payable->refresh()); }
}

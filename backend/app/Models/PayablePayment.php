<?php
namespace App\Models;
use App\Models\Concerns\BelongsToOrganization;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
#[Fillable(['organization_id','payable_id','recorded_by','cancelled_by','paid_at','amount_cents','method','reference','cancellation_reason','cancelled_at'])]
class PayablePayment extends Model { use BelongsToOrganization; protected function casts(): array { return ['paid_at'=>'datetime','amount_cents'=>'integer','cancelled_at'=>'datetime']; } public function recordedBy(): BelongsTo { return $this->belongsTo(User::class,'recorded_by'); } public function cancelledBy(): BelongsTo { return $this->belongsTo(User::class,'cancelled_by'); } }

<?php

namespace App\Models;

use App\Models\Traits\AttachmentComputations;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;


class Attachment extends Model
{
    use SoftDeletes;
    use AttachmentComputations;

    public static bool $filamentDetection = false;


    protected $fillable = [
        'name',
        'file_path',
        'user_id',
        'order_id',
        'payment_id',
        'payment_request_id',
        'proforma_invoice_id',
    ];

    protected $table = 'attachments';

    public function attachable(): MorphTo
    {
        return $this->morphTo();
    }

    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function paymentRequest()
    {
        return $this->belongsTo(PaymentRequest::class, 'payment_request_id');
    }

    public function proformaInvoice()
    {
        return $this->belongsTo(ProformaInvoice::class, 'proforma_invoice_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

//    protected static function booted()
//    {
//        $statusService = app(OrderPurchaseStatusService::class);
//
//        static::creating(fn($attachment) => $attachment->user_id = auth()->id());
//
//        static::saved(function (Attachment $attachment) use ($statusService) {
//            if ($attachment->order) {
//                $statusService->updateStatusBasedOnAttachments($attachment->order);
//            }
//
//            if ($attachment->wasChanged('order_id')) {
//                $originalOrderId = $attachment->getOriginal('order_id');
//                if ($originalOrderId && $originalOrder = Order::find($originalOrderId)) {
//                    $statusService->updateStatusBasedOnAttachments($originalOrder);
//                }
//            }
//        });
//
//        static::deleted(function (Attachment $attachment) use ($statusService) {
//            if ($attachment->order) {
//                $statusService->updateStatusBasedOnAttachments($attachment->order);
//            }
//
//            if (!$attachment->isUsedElsewhere() && $attachment->file_path && File::exists(public_path($attachment->file_path))) {
//                Storage::disk('public')->delete($attachment->file_path);
//            }
//        });
//    }

    protected static function booted()
    {
        static::creating(fn($attachment) => $attachment->user_id = auth()->id());

        static::deleted(function (Attachment $attachment) {
            if (!$attachment->isUsedElsewhere() && $attachment->file_path && File::exists(public_path($attachment->file_path))) {
                Storage::disk('public')->delete($attachment->file_path);
            }
        });
    }

//    private static function updateOrderStatuses(array $orderIds): void
//    {
//        $ids = array_values(array_unique(array_filter($orderIds, fn($v) => (int)$v > 0)));
//        if (empty($ids)) return;
//
//        static $processed = [];
//
//        $toProcess = array_values(array_diff($ids, array_keys($processed)));
//        if (empty($toProcess)) return;
//
//        foreach ($toProcess as $id) $processed[$id] = true;
//
//        $orders = Order::with(['attachments' => fn($q) => $q->select('id', 'order_id', 'name')])
//            ->select('id', 'order_status', 'purchase_status_id')
//            ->whereIn('id', $toProcess)
//            ->get();
//
//        $statusService = app(OrderPurchaseStatusService::class);
//
//        foreach ($orders as $order) {
//            $statusService->updateStatusBasedOnAttachments($order);
//        }
//    }
}

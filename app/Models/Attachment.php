<?php

namespace App\Models;

use App\Models\Traits\AttachmentComputations;
use App\Services\OrderPurchaseStatusService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;


class Attachment extends Model
{
    use HasFactory, SoftDeletes, AttachmentComputations;

    public static bool $filamentDetection = false;
    protected $fillable = [
        'name',
        'file_path',
        'extra',
        'user_id',
        'order_id',
        'payment_id',
        'payment_request_id',
        'proforma_invoice_id',
    ];
    protected $casts = [
        'extra' => 'json',
    ];
    protected $table = 'attachments';

    /**
     * Get the order associated with the attachment.
     */
    public function order()
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Get the payment associated with the attachment.
     */
    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    /**
     * Get the payment request associated with the attachment.
     */
    public function paymentRequest()
    {
        return $this->belongsTo(PaymentRequest::class, 'payment_request_id');
    }

    /**
     * Get the order request associated with the attachment.
     */
    public function proformaInvoice()
    {
        return $this->belongsTo(ProformaInvoice::class, 'proforma_invoice_id');
    }

    /**
     * Get the user that owns the attachment.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted()
    {
        $statusService = app(OrderPurchaseStatusService::class);

        static::creating(fn($attachment) => $attachment->user_id = auth()->id());

        static::saved(function (Attachment $attachment) use ($statusService) {
            if ($attachment->order) {
                $statusService->updateStatusBasedOnAttachments($attachment->order);
            }

            if ($attachment->wasChanged('order_id')) {
                $originalOrderId = $attachment->getOriginal('order_id');
                if ($originalOrderId && $originalOrder = Order::find($originalOrderId)) {
                    $statusService->updateStatusBasedOnAttachments($originalOrder);
                }
            }
        });

        static::deleted(function (Attachment $attachment) use ($statusService) {
            if ($attachment->order) {
                $statusService->updateStatusBasedOnAttachments($attachment->order);
            }

            if (!$attachment->isUsedElsewhere() && $attachment->file_path && File::exists(public_path($attachment->file_path))) {
                Storage::disk('public')->delete($attachment->file_path);
            }
        });
    }
}

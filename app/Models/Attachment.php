<?php

namespace App\Models;

use App\Models\Traits\AttachmentComputations;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;


class Attachment extends Model
{
    use HasFactory, SoftDeletes, AttachmentComputations;

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


    public static bool $filamentDetection = false;


    protected static function booted()
    {
        static::creating(function ($attachment) {
            $attachment->user_id = auth()->id() ?? null;
        });


        static::deleting(function ($attachment) {
            if (!$attachment->isUsedElsewhere() && $attachment->file_path && File::exists(public_path($attachment->file_path))) {
                Storage::disk('public')->delete($attachment->file_path);
            }
        });
    }

    /**
     * Get the user that owns the attachment.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

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
}

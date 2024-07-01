<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

class Attachment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'name',
        'file_path',
        'extra',
        'user_id',
        'order_id',
        'payment_id',
        'payment_request_id',
        'doc_id',
        'order_request_id',
        'logistic_id',
    ];

    protected $casts = [
        'extra' => 'json',
    ];


    protected $table = 'attachments';


    public static bool $filamentDetection = false;


    protected static function booted()
    {
        static::creating(function ($post) {
            $post->user_id = auth()->id() ?? null;
        });

        static::deleting(function ($attachment) {
            if ($attachment->file_path && File::exists(public_path($attachment->file_path))) {
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
     * Get the logistic associated with the attachment.
     */
    public function logistic()
    {
        return $this->belongsTo(Logistic::class);
    }

    /**
     * Get the payment request associated with the attachment.
     */
    public function paymentRequest()
    {
        return $this->belongsTo(PaymentRequest::class, 'payment_request_id');
    }

    /**
     * Get the document associated with the attachment.
     */
    public function document()
    {
        return $this->belongsTo(Doc::class, 'doc_id');
    }

    /**
     * Get the order request associated with the attachment.
     */
    public function orderRequest()
    {
        return $this->belongsTo(OrderRequest::class, 'order_request_id');
    }

}

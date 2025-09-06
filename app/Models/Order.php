<?php

namespace App\Models;

use App\Models\Traits\OrderComputations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory;
    use OrderComputations;
    use SoftDeletes;

    protected $fillable = [
        'order_number',
        'reference_number',
        'invoice_number',
        'part',
        'grade_id',
        'proforma_number',
        'proforma_date',
        'order_status',
        'extra',
        'proforma_invoice_id',
        'user_id',
        'purchase_status_id',
        'category_id',
        'product_id',
        'order_detail_id',
        'party_id',
        'logistic_id',
        'doc_id',
    ];

    protected $casts = [
        'extra' => 'json',
        'proforma_date' => 'date',
    ];

    protected $table = 'orders';

    public function associatedPaymentRequests()
    {
        return $this->proformaInvoice->associatedPaymentRequests();
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function doc()
    {
        return $this->belongsTo(Doc::class, 'doc_id');
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class, 'grade_id');
    }


    public function logistic()
    {
        return $this->belongsTo(Logistic::class, 'logistic_id');
    }


    public function names()
    {
        return $this->hasMany(Name::class);
    }

    public function notificationSubscriptions()
    {
        return $this->morphMany(NotificationSubscription::class, 'notifiable');
    }


    public function orderDetail()
    {
        return $this->belongsTo(OrderDetail::class, 'order_detail_id');
    }

    public function party()
    {
        return $this->belongsTo(Party::class,);
    }

    public function paymentRequests()
    {
        return $this->hasMany(PaymentRequest::class, 'order_id');
    }

    public function payments()
    {
        return $this->belongsToMany(
            Payment::class,
            'payment_payment_request',
            'payment_request_id',
            'payment_id'
        );
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function proformaInvoice()
    {
        return $this->belongsTo(ProformaInvoice::class, 'proforma_invoice_id', 'id');
    }

    public function purchaseStatus()
    {
        return $this->belongsTo(PurchaseStatus::class, 'purchase_status_id');
    }

    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'order_tag', 'order_id', 'tag_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted()
    {
        static::creating(function ($order) {
            $order->user_id = auth()->id();
            $order->order_number = self::makeOrderNumber($order);
        });

        static::saved(function ($order) {
            $order->loadMissing('attachments');
            $order->attachments()
                ->where(function ($q) {
                    $q->whereNull('file_path')
                        ->orWhere('file_path', '')
                        ->orWhereNull('name')
                        ->orWhere('name', '');
                })
                ->delete();
        });

        static::updating(function ($order) {
            $order->order_number = self::makeOrderNumber($order);
        });
    }
}

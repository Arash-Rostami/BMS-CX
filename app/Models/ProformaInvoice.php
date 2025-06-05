<?php

namespace App\Models;

use App\Models\Traits\ProformaInvoiceComputations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;


class ProformaInvoice extends Model
{
    use HasFactory, Notifiable, SoftDeletes, ProformaInvoiceComputations;

    protected $table = 'proforma_invoices';

    protected $fillable = [
        'grade_id',
        'quantity',
        'price',
        'details',
        'status',
        'extra',
        'proforma_number',
        'proforma_date',
        'contract_number',
        'user_id',
        'assignee_id',
        'category_id',
        'product_id',
        'buyer_id',
        'supplier_id',
        'percentage',
        'reference_number',
        'part',
        'verified',
        'verified_by',
        'verified_at'
    ];

    protected $casts = [
        'details' => 'json',
        'extra' => 'json',
        'proforma_status' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'proforma_date' => 'date',
        'verified' => 'boolean',
        'verified_at' => 'datetime',
    ];


    protected static function booted()
    {
        static::creating(function ($proformaInvoice) {
            $proformaInvoice->user_id = auth()->id();
        });

        static::updating(function ($proformaInvoice) {
            $dirty = $proformaInvoice->getDirty();
            $changedAttributes = array_diff(array_keys($dirty), ['verified', 'verified_by', 'verified_at']);
            if (!empty($changedAttributes)) {
                $proformaInvoice->verified = false;
                $proformaInvoice->verified_by = null;
                $proformaInvoice->verified_at = null;
            }
        });

        static::saving(function ($proformaInvoice) {
            $proformaInvoice->attachments->each(function ($attachment) {
                if (empty($attachment->file_path) || empty($attachment->name)) {
                    $attachment->delete();
                }
            });
        });
    }

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    public function buyer()
    {
        return $this->belongsTo(Buyer::class, 'buyer_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class, 'grade_id');
    }

    public function names()
    {
        return $this->hasMany(Name::class);
    }

    public function notificationSubscriptions()
    {
        return $this->morphMany(NotificationSubscription::class, 'notifiable');
    }

    public function orders()
    {
        return $this->hasMany(Order::class, 'proforma_invoice_id');
    }

    public function activeOrders()
    {
        return $this->hasMany(Order::class, 'proforma_invoice_id')
            ->whereNull('deleted_at');
    }

    public function paymentRequests()
    {
        return $this->hasOneThrough(PaymentRequest::class, Order::class, 'proforma_invoice_id', 'order_id');
    }

    public function associatedPaymentRequests()
    {
        return $this->belongsToMany(
            PaymentRequest::class,
            'payment_request_proforma_invoice',
            'proforma_invoice_id',
            'payment_request_id'
        );
    }

    public function activeApprovedPaymentRequests()
    {
        return $this->belongsToMany(
            PaymentRequest::class,
            'payment_request_proforma_invoice',
            'proforma_invoice_id',
            'payment_request_id'
        )
            ->whereNull('order_id')
            ->whereNull('deleted_at')
            ->whereNotIn('status', ['pending', 'cancelled', 'rejected']);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function assignee()
    {
        return $this->belongsTo(User::class, 'assignee_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function verifier()
    {
        return $this->belongsTo(User::class, 'verified_by');
    }
}

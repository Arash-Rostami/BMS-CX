<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;


class ProformaInvoice extends Model
{
    use HasFactory, Notifiable;
    use SoftDeletes;

    protected $table = 'proforma_invoices';


    protected $fillable = [
        'grade',
        'quantity',
        'price',
        'details',
        'status',
        'extra',
        'proforma_number',
        'proforma_date',
        'contract_number',
        'user_id',
        'category_id',
        'product_id',
        'buyer_id',
        'supplier_id',
        'percentage',
        'reference_number',
        'part'
    ];

    protected $casts = [
        'details' => 'json',
        'extra' => 'json',
        'proforma_status' => 'string',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'proforma_date' => 'date',
    ];

    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    protected static function booted()
    {
        static::creating(function ($proformaInvoice) {
            $proformaInvoice->user_id = auth()->id();
        });


        static::saving(function ($proformaInvoice) {
            $proformaInvoice->attachments->each(function ($attachment) {
                if (empty($attachment->file_path) || empty($attachment->name)) {
                    $attachment->delete();
                }
            });
        });
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
            ->whereIn('status', ['approved', 'allowed']);
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }


    // Computational Methods
    public static function fetchProformasByID($id)
    {
        return self::find($id)
            ->where('status', 'approved')
            ->whereNull('deleted_at')
            ->get(['quantity', 'price', 'percentage']);
    }

    public static function fetchApprovedProformas($proformaNumbers)
    {
        return self::whereIn('proforma_number', $proformaNumbers)
            ->where('status', 'approved')
            ->whereNull('deleted_at')
            ->with(['orders.orderDetail'])
            ->get();
    }


    public static function fetchActiveApprovedProformas($paymentRequests)
    {
        $relatedProformaInvoices = collect();

        foreach ($paymentRequests as $paymentRequest) {
            $proformaInvoices = $paymentRequest->activeApprovedProformaInvoices;
            $relatedProformaInvoices = $relatedProformaInvoices->merge($proformaInvoices);
        }

        return $relatedProformaInvoices->unique('id');
    }

    public function getDaysPassedAttribute()
    {
        return Carbon::parse($this->proforma_date)->diffInDays(now());
    }

    public static function getDistinctProformaNumbers()
    {
        return static::distinct('proforma_number')
            ->pluck('proforma_number')
            ->mapWithKeys(function ($item) {
                return [$item => $item];
            });
    }


    public static function getFormattedProformaNumbers()
    {
        return static::all()
            ->mapWithKeys(function ($item) {
                $product = optional($item->product)->name ?? 'Undefined Product';
                $grade = optional($item->grade)->name ?? 'Undefined Grade';
                return [$item->id => "$item->proforma_number ($product - $grade)  💢 Ref: $item->reference_number"];
            });
    }

    public static function getProformaInvoicesCached()
    {
        $key = 'proforma_invoices_list';

        if (Cache::has($key)) {
            $proformaInvoices = Cache::get($key);
        } else {
            $proformaInvoices = ProformaInvoice::pluck('reference_number', 'id')->toArray();
            Cache::put($key, $proformaInvoices, 5);
        }

        return $proformaInvoices;
    }

    public static function hasMatchingProformaNumber(string $search)
    {
        return self::where('proforma_number', 'like', "%{$search}%")
            ->exists();
    }

    public static function getProformaInvoicesWithSearch(string $search)
    {
        return self::where('proforma_number', 'like', "%{$search}%")
            ->with(['product:id,name'])
            ->select('id', 'proforma_number', 'reference_number', 'product_id', 'grade_id')
            ->limit(20)
            ->get();
    }

    public static function getStatusCounts()
    {
        $cacheKey = 'proforma_invoice_status_counts';

        return Cache::remember($cacheKey, 60, function () {
            return static::select('status')
                ->selectRaw('count(*) as count')
                ->groupBy('status')
                ->get()
                ->keyBy('status')
                ->map(fn($item) => $item->count);
        });
    }

    public static function getApproved()
    {
        $cacheKey = 'approved_proforma_invoices';

        Cache::forget('approved_proforma_invoices');
        return Cache::remember($cacheKey, 60, function () {
            return static::where('status', 'approved')
                ->with('product', 'category', 'buyer')
                ->orderBy('id', 'desc')
                ->get()
                ->pluck('formatted_value', 'id');
        });
    }

    public function getFormattedValueAttribute()
    {
        $proformaInvoice = $this->proforma_number ?? '';
        $referenceNumber = $this->reference_number ?? sprintf('PI-%s%04d', $this->created_at->format('y'), $this->id);

        if ($proformaInvoice) {
            return sprintf('%s 💢 Ref: %s', $proformaInvoice, $referenceNumber);
        }

        return sprintf(
            '%s - %s (%s) 💢 Ref: %s',
            $this->buyer->name,
            $this->product->name,
            $this->category->name,
            $referenceNumber
        );
    }

    public function setExtraAttribute($value)
    {
        if (is_array($value) && isset($value['port'])) {
            $value['port'] = array_map('strtoupper', $value['port']);
        }
        $this->attributes['extra'] = json_encode($value);
    }

    public function showSearchResult()
    {
        return sprintf(
            "%s (%s - %s) 💢 Ref: %s",
            $this->proforma_number ?? 'N/A',
            optional($this->product)->name ?? 'N/A',
            optional($this->grade)->name ?? 'N/A',
            $this->reference_number ?? 'N/A'
        );
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;

class OrderRequest extends Model
{
//    use HasFactory, Notifiable;
//    use SoftDeletes;
//
//    protected $table = 'order_requests';
//
//    protected $fillable = [
//        'grade', 'quantity', 'price', 'details', 'request_status', 'extra',
//        'user_id', 'category_id', 'product_id', 'buyer_id', 'supplier_id'
//    ];
//
//    protected $casts = [
//        'details' => 'json',
//        'extra' => 'json',
//        'request_status' => 'string',
//    ];
//
//    public function attachments()
//    {
//        return $this->hasMany(Attachment::class);
//    }
//
//
//    protected static function booted()
//    {
//        static::creating(function ($post) {
//            $post->user_id = auth()->id();
//        });
//    }
//
//    public static function getStatusCounts()
//    {
//        $cacheKey = 'order_request_status_counts';
//
//        return Cache::remember($cacheKey, 60, function () {
//            return static::select('request_status')
//                ->selectRaw('count(*) as count')
//                ->groupBy('request_status')
//                ->get()
//                ->keyBy('request_status')
//                ->map(fn($item) => $item->count);
//        });
//    }
//
//    public static function getApproved()
//    {
//        $cacheKey = 'approved_order_requests';
//
//        Cache::forget('approved_order_requests');
//        return Cache::remember($cacheKey, 60, function () {
//            return static::where('request_status', 'approved')
//                ->with('product', 'category', 'buyer')
//                ->orderBy('id', 'desc')
//                ->get()
//                ->pluck('formatted_value', 'id');
//        });
//    }
//
//    public function getFormattedValueAttribute()
//    {
//        $proformaInvoice = optional($this->extra)['proforma_number'];
//        $referenceNumber = $this->extra['reference_number'] ?? sprintf('PI-%s%04d', $this->created_at->format('y'), $this->id);
//
//        if ($proformaInvoice) {
//            return sprintf('%s 💢 Ref: %s', $proformaInvoice, $referenceNumber);
//        }
//
//        return sprintf(
//            '%s - %s (%s) 💢 Ref: %s',
//            $this->buyer->name,
//            $this->product->name,
//            $this->category->name,
//            $referenceNumber
//        );
//    }
//
//
//    public function user()
//    {
//        return $this->belongsTo(User::class, 'user_id');
//    }
//
//    public function category()
//    {
//        return $this->belongsTo(Category::class, 'category_id');
//    }
//
//    public function orders()
//    {
//        return $this->hasMany(Order::class, 'order_request_id');
//    }
//
//    public function paymentRequests()
//    {
//        return $this->hasManyThrough(PaymentRequest::class, Order::class, 'order_request_id', 'order_invoice_number', 'id', 'invoice_number');
//    }
//
//    public function product()
//    {
//        return $this->belongsTo(Product::class, 'product_id');
//    }
//
//    public function buyer()
//    {
//        return $this->belongsTo(Buyer::class, 'buyer_id');
//    }
//
//    public function supplier()
//    {
//        return $this->belongsTo(Supplier::class, 'supplier_id');
//    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderRequest extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $table = 'order_requests';

    protected $fillable = [
        'grade', 'quantity', 'price', 'details', 'request_status',
        'user_id', 'category_id', 'product_id', 'buyer_id', 'supplier_id'
    ];

    protected $casts = [
        'details' => 'array',
        'request_status' => 'string',
    ];

    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'order_request_id');
    }

    protected static function booted()
    {
        static::creating(function ($post) {
            $post->user_id = auth()->id();
        });
    }

    public static function getStatusCounts()
    {
        return static::select('request_status')
            ->selectRaw('count(*) as count')
            ->groupBy('request_status')
            ->get()
            ->keyBy('request_status')
            ->map(fn($item) => $item->count);
    }

    public static function getApproved()
    {
        return static::where('request_status', 'approved')
            ->with('product', 'category', 'buyer')
            ->get()
            ->pluck('formatted_value', 'id');
    }


    public function getFormattedValueAttribute()
    {
        return "{$this->buyer->name} - {$this->product->name} ({$this->category->name})  - {$this->created_at->format('Y-m-d')}";
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function buyer()
    {
        return $this->belongsTo(Buyer::class, 'buyer_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }
}

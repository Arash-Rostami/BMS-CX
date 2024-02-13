<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Order extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'order_number',
        'invoice_number',
        'part',
        'grade',
        'proforma_number',
        'proforma_date',
        'order_status',
        'extra',
        'order_request_id',
        'user_id',
        'purchase_status_id',
        'category_id',
        'product_id',
        'order_detail_id',
        'party_id',
        'logistic_id',
        'doc_id',
        'attachment_id',
    ];

    protected $casts = [
        'extra' => 'json',
        'proforma_date' => 'date',
    ];

    protected $table = 'orders';


    public function attachments()
    {
        return $this->hasMany(Attachment::class);
    }

    protected static function booted()
    {
        static::creating(function ($post) {
            $post->user_id = auth()->id();
            $post->order_number = self::makeOrderNumber($post);
        });

        static::updating(function ($post) {
            $post->order_number = self::makeOrderNumber($post);
        });
    }

    /**
     * Get the category associated with the order.
     */
    public function category()
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    /**
     * Get the doc associated with the order.
     */
    public function doc()
    {
        return $this->belongsTo(Doc::class, 'doc_id');
    }

    /**
     * Get the logistic associated with the order.
     */
    public function logistic()
    {
        return $this->belongsTo(Logistic::class, 'logistic_id');
    }



    public static function getStatusCounts()
    {
        return static::select('purchase_status_id')
            ->selectRaw('count(*) as count')
            ->groupBy('purchase_status_id')
            ->get()
            ->keyBy('purchase_status_id')
            ->map(fn($item) => $item->count);
    }


    public static function makeOrderNumber($post): string
    {
        $category = "C" . $post->category_id;
        $product = "P" . $post->product_id;
        $proforma = "PR" . $post->proforma_number;
        $party = "PA" . $post->party_id;
        $orderDetail = "OD" . $post->order_detail_id;
        $logistic = "L" . $post->logistic_id;
        $doc = "D" . $post->doc_id;

        return $category . $product . $proforma . $party . $orderDetail . $logistic . $doc;
    }


//    /**
//     * Get the payment requests for the user.
//     */
//    public function paymentRequests()
//    {
//        return $this->hasMany(PaymentRequest::class, 'pa');
//    }


    /**
     * Get the stock associated with the order.
     */
    public function orderDetail()
    {
        return $this->belongsTo(OrderDetail::class, 'order_detail_id');
    }

    /**
     * Get the stock associated with the order.
     */
    public function orderRequest()
    {
        return $this->belongsTo(OrderRequest::class, 'order_request_id');
    }

    /**
     * Get the party associated with the order.
     */
    public function party()
    {
        return $this->belongsTo(Party::class,);
    }


    /**
     * Get the product associated with the order.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Get the logistic associated with the order.
     */
    public function purchaseStatus()
    {
        return $this->belongsTo(PurchaseStatus::class, 'purchase_status_id');
    }
    /**
     * @param $post
     */
    /**
     * Get the user that owns the order.
     * //     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

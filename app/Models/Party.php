<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Party extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'order_id',
        'packaging_id',
        'buyer_id',
        'supplier_id',
        'extra',
    ];

    protected $casts = [
        'extra' => 'json',
    ];

    public static bool $filamentDetection = false;
    public static string $filamentName = 'PART';

    protected static function booted()
    {
        static::creating(function ($post) {
            $post->user_id = auth()->id();
        });
    }

    /**
     * Get the user that owns the party.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order associated with the party.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Get the packaging associated with the party.
     */
    public function packaging()
    {
        return $this->belongsTo(Packaging::class);
    }

    /**
     * Get the buyer associated with the party.
     */
    public function buyer()
    {
        return $this->belongsTo(Buyer::class);
    }

    /**
     * Get the supplier associated with the party.
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class);
    }
}

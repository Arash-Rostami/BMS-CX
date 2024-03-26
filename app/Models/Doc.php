<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Doc extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'voyage_number',
        'declaration_number',
        'declaration_date',
        'BL_number',
        'BL_date',
        'extra',
        'user_id',
        'order_id',
    ];

    protected $casts = [
        'extra' => 'json',
        'declaration_date' => 'date',
        'BL_date' => 'date',
    ];

    public static bool $filamentDetection = false;



    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'doc_id');
    }

    protected static function booted()
    {
        static::creating(function ($post) {
            $post->user_id = auth()->id();
        });
    }
    /**
     * Get the user that owns the doc.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order associated with the doc.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }
}

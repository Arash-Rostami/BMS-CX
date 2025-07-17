<?php

namespace App\Models;

use App\Models\Traits\OrderedStage;
use App\Models\Traits\PurchaseStatusComputations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseStatus extends Model
{
    use HasFactory, OrderedStage, PurchaseStatusComputations;

    protected $fillable = ['name', 'description', 'user_id'];

    public const SORTED_ORDER = [
        '⏳ Pending',
        '🚧 In Transit',
        '👮 Customs ',
        '🚚 Delivered ',
        '🚢 Shipped',
        '🆓 Released',
    ];


    protected static function booted()
    {
        static::creating(function ($post) {
            $post->user_id = auth()->id();
        });
    }

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}

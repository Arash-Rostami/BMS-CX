<?php

namespace App\Models;

use App\Models\Traits\OrderedStage;
use App\Models\Traits\PurchaseStatusComputations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Rennokki\QueryCache\Traits\QueryCacheable;

class PurchaseStatus extends Model
{
    use HasFactory;
    use OrderedStage;
    use PurchaseStatusComputations;
    use QueryCacheable;

    public $cacheFor = 86400;
    public $cacheDriver = 'file';
    public $cacheTags = ['purchase_statuses_table'];
    protected static $flushCacheOnUpdate = true;

    public const SORTED_ORDER = [
        '⏳ Pending',
        '🚧 In Transit',
        '👮 Customs ',
        '🚚 Delivered ',
        '🚢 Shipped',
        '🆓 Released',
    ];
    protected $fillable = ['name', 'description', 'user_id'];

    public function orders()
    {
        return $this->hasMany(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    protected static function booted()
    {
        static::creating(function ($post) {
            $post->user_id = auth()->id();
        });
    }
}

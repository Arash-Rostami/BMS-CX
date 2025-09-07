<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Rennokki\QueryCache\Traits\QueryCacheable;

class Contractor extends Model
{
    use HasFactory;
    use QueryCacheable;

    public $cacheFor = 86400;
    public $cacheDriver = 'file';
    public $cacheTags = ['contractors_table'];
    protected static $flushCacheOnUpdate = true;

    protected $fillable = [
        'name', 'description', 'user_id',
    ];

    protected static function booted()
    {
        static::creating(function ($contractor) {
            $contractor->user_id = auth()->id();
        });
    }

    /**
     * Get the user that owns the contractor.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

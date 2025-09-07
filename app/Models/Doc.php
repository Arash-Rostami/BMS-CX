<?php

namespace App\Models;

use App\Models\Traits\DocCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Rennokki\QueryCache\Traits\QueryCacheable;

class Doc extends Model
{
    use HasFactory;
    use SoftDeletes;
    use DocCache;

    use QueryCacheable;

    public static bool $filamentDetection = false;
    protected static $flushCacheOnUpdate = true;
    public $cacheFor = 86400;
    public $cacheDriver = 'file';
    public $cacheTags = ['docs_table'];
    protected $fillable = [
        'voyage_number',
        'declaration_number',
        'declaration_date',
        'BL_number',
        'BL_date',
        'extra',
        'user_id',
    ];

    protected $casts = [
        'extra' => 'json',
        'declaration_date' => 'date',
        'BL_date' => 'date',
    ];


    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'doc_id');
    }

    public function order()
    {
        return $this->hasOne(Order::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    protected static function booted()
    {
        static::creating(function ($doc) {
            $doc->user_id = auth()->id();
        });
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Rennokki\QueryCache\Traits\QueryCacheable;

class Permission extends Model
{
    use QueryCacheable;

    protected static $flushCacheOnUpdate = true;
    public $cacheFor = 864000;
    public $cacheDriver = 'file';
    public $cacheTags = ['permission_table'];

    protected $fillable = ['user_id', 'permission', 'model'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

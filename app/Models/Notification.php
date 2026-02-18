<?php

namespace App\Models;

use App\Models\Traits\NotificationComputations;
use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;
use Rennokki\QueryCache\Traits\QueryCacheable;

class Notification extends Model
{
    use Notifiable;
    use NotificationComputations;
    use SoftDeletes;
    use QueryCacheable;

    protected static $flushCacheOnUpdate = true;
    public $cacheFor = 900;
    public $cacheDriver = 'file';
    public $cacheTags = ['notifications_table'];


    protected $table = 'notifications';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'id',
        'type',
        'notifiable_type',
        'notifiable_id',
        'data',
        'read_at',
        'created_at',
        'deleted_at'
    ];

    public function forceDelete()
    {
        // Option 2: Throw an exception if someone tries to force delete
        throw new Exception('Hard deletes are disabled for this model.');
    }


    public function user()
    {
        return $this->belongsTo(User::class, 'notifiable_id');
    }
}



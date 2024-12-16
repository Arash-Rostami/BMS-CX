<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Notification extends Model
{
    use SoftDeletes;
    use HasFactory, Notifiable;


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
        throw new \Exception("Hard deletes are disabled for this model.");
    }


    public function getDeletedAtAttribute()
    {
        if (is_null($this->attributes['deleted_at'])) {
            return 'Uncleared';
        }

        return $this->attributes['deleted_at'];
    }

    public function getCreatedAtAttribute()
    {
        if (is_null($this->attributes['created_at'])) {
            return 'Unsent';
        }

        return $this->attributes['created_at'];
    }

    public function getReadAtAttribute()
    {
        if (is_null($this->attributes['read_at'])) {
            return 'Unread';
        }

        return $this->attributes['read_at'];
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'notifiable_id');
    }

    public function scopeFilterByUserRole(Builder $query, $user): Builder
    {
        if ($user->role != 'admin') {
            return $query->where('notifiable_id', $user->id);
        }

        return $query;
    }
}



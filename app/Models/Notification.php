<?php

namespace App\Models;

use App\Models\Traits\NotificationComputations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Notifications\Notifiable;

class Notification extends Model
{
    use SoftDeletes, HasFactory, Notifiable, NotificationComputations;


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


    public function user()
    {
        return $this->belongsTo(User::class, 'notifiable_id');
    }
}



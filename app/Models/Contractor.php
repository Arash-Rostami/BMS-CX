<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contractor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name', 'description', 'user_id',
    ];

    protected static function booted()
    {
        static::creating(function ($contractor) {
            $contractor->user_id = auth()->id();
        });
    }

    public function orderRequests()
    {
        return $this->hasMany(OrderRequest::class);
    }


    /**
     * Get the user that owns the contractor.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

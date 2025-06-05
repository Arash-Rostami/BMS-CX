<?php

namespace App\Models;

use App\Events\ChatMessageCreated;
use App\Events\ChatMessageUpdated;
use App\Models\Traits\ChatComputations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory, ChatComputations;

    public static $filamentDetection = false;

    protected $fillable = [
        'user_id',
        'record_id',
        'record_type',
        'message',
        'mentions',
        'extra'
    ];

    protected $casts = [
        'mentions' => 'array',
        'extra' => 'json'
    ];

    protected static function booted()
    {
        static::creating(function ($chat) {
            $chat->user_id = auth()->id();
            event(new ChatMessageCreated($chat));
        });

        static::updated(function ($chat) {
            event(new ChatMessageUpdated($chat));
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function record()
    {
        return $this->morphTo();
    }
}

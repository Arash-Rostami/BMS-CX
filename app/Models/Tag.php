<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Tag extends Model
{
    use HasFactory;

    public static $filamentDetection = false;


    protected $fillable = [
        'name',
        'module',
        'extra',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'extra' => 'array',
    ];

    public static function boot()
    {
        parent::boot();

        static::creating(function ($tag) {
            $tag->created_by = auth()->user()->id ?? null;
        });

        static::updating(function ($tag) {
            $tag->updated_by = auth()->user()->id ?? null;
        });
    }

    public function order()
    {
        return $this->belongsToMany(Order::class, 'order_tag', 'tag_id', 'order_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'created_by');
    }


    // Computational Methods
    public static function deleteEmptyOrderTags($module)
    {
        self::where('module', $module)
            ->where(function ($query) {
                $query->where('extra', '[]')->orWhereNull('extra');
            })
            ->delete();
    }

    public static function formatTags($tags)
    {
        if ($tags->isEmpty()) {
            return 'No Tag Assigned';
        }
        return $tags->unique()->join(', ');
    }

    public function scopeFilteredForUser(Builder $query, int $userId): Builder
    {
        return $query->where(function ($query) use ($userId) {
            $query->where('created_by', $userId)
                ->orWhere('updated_by', $userId);
        })
            ->where('module', 'Order')
            ->where('extra', '!=', '[]')
            ->whereNotNull('extra');
    }
}

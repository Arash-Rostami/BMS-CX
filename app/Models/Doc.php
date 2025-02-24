<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Cache;

class Doc extends Model
{
    use HasFactory;
    use SoftDeletes;

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

    public static bool $filamentDetection = false;



    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'doc_id');
    }

    protected static function booted()
    {
        static::creating(function ($doc) {
            $doc->user_id = auth()->id();
        });
    }
    /**
     * Get the user that owns the doc.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public static function getLatestBLDate(): ?string
    {
        return Cache::remember('latest_bl_date', now()->addMinutes(15), function () {
            $latestBLDate = self::whereNotNull('BL_date')
                ->orderByDesc('BL_date')
                ->value('BL_date');

            return $latestBLDate ? Carbon::parse($latestBLDate)->format('j F Y') : 'N/A';
        });
    }
}

<?php

namespace App\Models;

use App\Models\Traits\DocCache;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Doc extends Model
{
    use HasFactory, SoftDeletes, DocCache;

    public static bool $filamentDetection = false;


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

    protected static function booted()
    {
        static::creating(function ($doc) {
            $doc->user_id = auth()->id();
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}

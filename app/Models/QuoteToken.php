<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QuoteToken extends Model
{

    public static $filamentDetection = false;

    protected $fillable = [
        'token',
        'quote_id',
        'validity',
        'quote_request_id',
        'quote_provider_id',
    ];

    public static function countNum($id)
    {
        return static::where('quote_request_id', $id)->count();
    }

    // Relationships (potential)

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }

    public function quoteProvider()
    {
        return $this->belongsTo(QuoteProvider::class);
    }

    public function quoteRequest()
    {
        return $this->belongsTo(QuoteRequest::class);
    }
}

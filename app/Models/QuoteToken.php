<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuoteToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'token',
        'quote_id',
        'validity',
        'quote_request_id',
        'quote_provider_id',
    ];

    // Relationships (potential)
    public function quoteRequest()
    {
        return $this->belongsTo(QuoteRequest::class);
    }

    public function quoteProvider()
    {
        return $this->belongsTo(QuoteProvider::class);
    }

    public function quote()
    {
        return $this->belongsTo(Quote::class);
    }
}

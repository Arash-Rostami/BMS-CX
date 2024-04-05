<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{

    use HasFactory, SoftDeletes;

    protected $fillable = [
        'transportation_means',
        'transportation_type',
        'origin_port',
        'destination_port',
        'offered_rate',
        'switch_bl_fee',
        'commodity_type',
        'packing_type',
        'payment_terms',
        'free_time_pol',
        'free_time_pod',
        'validity',
        'extra',
        'quote_request_id',
        'quote_provider_id',
        'attachment_id',
    ];

    public static function countNum($id)
    {
        return self::where('quote_request_id', $id)->count();
    }

    public function quoteRequest()
    {
        return $this->belongsTo(QuoteRequest::class, 'quote_request_id');
    }

    public function quoteProvider()
    {
        return $this->belongsTo(QuoteProvider::class);
    }

    public function attachment()
    {
        return $this->belongsTo(Attachment::class);
    }

    public function hasAttachment()
    {
        return $this->attachment()->exists();
    }
}

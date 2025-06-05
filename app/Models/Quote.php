<?php

namespace App\Models;

use App\Models\Traits\QuoteComputations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Quote extends Model
{

    use HasFactory, SoftDeletes, QuoteComputations;

    protected $fillable = [
        'container_number',
        'container_type',
        'origin_port',
        'destination_port',
        'offered_rate',
        'local_charges',
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

    public function product()
    {
        return $this->belongsTo(Product::class, 'commodity_type');
    }
}

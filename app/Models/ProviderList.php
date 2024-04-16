<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProviderList extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'exclude', 'extra'];

    public function quoteProviders()
    {
        return $this->belongsToMany(QuoteProvider::class, 'provider_list_quote_provider', 'provider_list_id', 'quote_provider_id');
    }
}

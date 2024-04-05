<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class QuoteProvider extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'name',
        'company',
        'email',
        'phone_number',
        'extra',
    ];

    public function quoteTokens()
    {
        return $this->hasMany(QuoteToken::class);
    }

    public function quotes() // Optional - depends on your data structure
    {
        return $this->hasManyThrough(Quote::class, QuoteToken::class);
    }
}

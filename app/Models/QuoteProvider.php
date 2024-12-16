<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class QuoteProvider extends Model
{
    use HasFactory, Notifiable;


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

    public function quotes()
    {
        return $this->hasManyThrough(Quote::class, QuoteToken::class);
    }
}

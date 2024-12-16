<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Beneficiary extends Model
{
    use HasFactory;


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'payee_type',
        'economic_code',
        'national_id',
        'name',
        'phone_number',
        'address',
        'extra',
        'vat',
    ];

    /**
     * Cast booleans to actual boolean values.
     *
     * @var array
     */
    protected $casts = [
        'vat' => 'boolean',
    ];
}

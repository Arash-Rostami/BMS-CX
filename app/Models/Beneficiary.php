<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Beneficiary extends Model
{


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

    protected $casts = [
        'vat' => 'boolean',
    ];
}

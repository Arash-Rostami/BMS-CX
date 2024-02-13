<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Payment extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'payer',
        'number',
        'beneficiary_name',
        'beneficiary_address',
        'bank_name',
        'account_number',
        'swift_code',
        'IBAN',
        'amount',
        'currency',
        'IFSC',
        'MICR',
        'extra',
        'user_id',
        'order_id',
    ];

    protected $casts = [
        'extra' => 'json',
    ];

    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'payment_id');
    }

    /**
     * Get the user that owns the payment.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the order that the payment belongs to.
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ContractOverview extends Model
{


    public $incrementing = false;
    public $timestamps = false;

    protected $table = 'view_contract';
    protected $primaryKey = 'pi_id';

    protected $casts = [
        'pi_date' => 'date',
        'pi_quantity' => 'decimal:2',
        'bl_date' => 'date',
        'order_detail_quantity' => 'decimal:2',
        'order_deleted_at' => 'datetime',
        'doc_deleted_at' => 'datetime',
        'order_detail_deleted_at' => 'datetime',
    ];
}

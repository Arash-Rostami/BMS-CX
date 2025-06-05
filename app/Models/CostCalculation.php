<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CostCalculation extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'cost_calculations';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'product_id',
        'grade_id',
        'supplier_id',
        'packaging_id',
        'user_id',
        'tender_no',
        'date',
        'validity',
        'quantity',
        'term',
        'win_price_usd',
        'persol_price_usd',
        'price_difference',
        'cfr_china',
        'status',
        'note',
        'transport_type',
        'transport_cost',
        'container_type',
        'thc_cost',
        'stuffing_cost',
        'ocean_freight',
        'exchange_rate',
        'extra',
        'additional_costs',
        'total_cost',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'date' => 'date',
        'validity' => 'date',
        'quantity' => 'decimal:2',
        'win_price_usd' => 'decimal:2',
        'persol_price_usd' => 'decimal:2',
        'price_difference' => 'decimal:2',
        'cfr_china' => 'decimal:2',
        'transport_cost' => 'decimal:2',
        'thc_cost' => 'decimal:2',
        'stuffing_cost' => 'decimal:2',
        'ocean_freight' => 'decimal:2',
        'exchange_rate' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'extra' => 'array',
        'additional_costs' => 'array',
    ];


    /**
     * Get the product associated with the cost calculation.
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get the grade associated with the cost calculation.
     */
    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    /**
     * Get the supplier associated with the cost calculation.
     */
    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    /**
     * Get the packaging associated with the cost calculation.
     */
    public function packaging(): BelongsTo
    {
        return $this->belongsTo(Packaging::class);
    }

    /**
     * Get the user who created/updated the cost calculation.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}

<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

class Logistic extends Model
{
    use HasFactory;
    use SoftDeletes;

    public static bool $filamentDetection = false;


    protected $casts = [
        'loading_deadline' => 'date',
        'extra' => 'json',
    ];

    protected $fillable = [
        'loading_deadline',
        'change_of_destination',
        'number_of_containers',
        'full_container_load_type',
        'ocean_freight',
        'terminal_handling_charges',
        'FCL',
        'booking_number',
        'free_time_POD',
        'gross_weight',
        'net_weight',
        'extra',
        'user_id',
        'shipping_line_id',
        'port_of_delivery_id',
        'delivery_term_id',
        'packaging_id',
    ];

    protected static function booted()
    {
        static::creating(function ($logistic) {
            $logistic->user_id = auth()->id();
        });
    }


    public function attachments()
    {
        return $this->hasMany(Attachment::class, 'logistic_id');
    }


    /**
     * Get the user that owns the logistic.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }


    /**
     * Get the packaging associated with the party.
     */
    public function packaging()
    {
        return $this->belongsTo(Packaging::class);
    }


    /**
     * Get the shipping line associated with the logistic.
     */
    public function shippingLine()
    {
        return $this->belongsTo(ShippingLine::class);
    }

    /**
     * Get the port of delivery associated with the logistic.
     */
    public function portOfDelivery()
    {
        return $this->belongsTo(PortOfDelivery::class);
    }

    /**
     * Get the delivery term associated with the logistic.
     */
    public function deliveryTerm()
    {
        return $this->belongsTo(DeliveryTerm::class);
    }

    public function getLoadingStartlineAttribute()
    {
        return isset($this->extra['loading_startline'])
            ? Carbon::parse($this->extra['loading_startline'])
            : null;
    }

    public function getEtaAttribute()
    {
        return isset($this->extra['eta'])
            ? Carbon::parse($this->extra['eta'])
            : null;
    }

    public function getEtdAttribute()
    {
        return isset($this->extra['etd'])
            ? Carbon::parse($this->extra['etd'])
            : null;
    }


    public static function countByPackagingType($year)
    {
        $cacheKey = 'orders_data_by_category_' . $year;

        return Cache::remember($cacheKey, 300, function () use ($year) {
            $query = self::query()
                ->with('packaging')
                ->selectRaw('packaging_id, count(*) as total')
                ->groupBy('packaging_id');

            if ($year !== 'all') {
                $query->whereHas('order', function ($subQuery) use ($year) {
                    $subQuery->whereYear('proforma_date', $year);
                });
            }

            return $query->get()
                ->map(function ($item) {
                    return [
                        'name' => $item->packaging ? $item->packaging->name : 'Unknown',
                        'total' => $item->total
                    ];
                });
        });
    }
}

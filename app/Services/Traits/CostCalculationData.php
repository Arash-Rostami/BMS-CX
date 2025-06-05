<?php

namespace App\Services\Traits;

use App\Models\Grade;
use App\Models\Packaging;
use App\Models\Product;
use App\Models\Supplier;
use Illuminate\Support\Facades\Cache;

trait CostCalculationData
{
    use CostCalculationOptions;

    public $rules = [
        'formData.product_id' => 'required|exists:products,id',
        'formData.grade_id' => 'nullable|exists:grades,id',
        'formData.supplier_id' => 'required|exists:suppliers,id',
        'formData.packaging_id' => 'nullable|exists:packagings,id',
        'formData.tender_no' => 'nullable|string|max:255',
        'formData.date' => 'nullable|date',
        'formData.validity' => 'nullable|date',
        'formData.quantity' => 'nullable|numeric',
        'formData.term' => 'nullable|string|max:255',
        'formData.win_price_usd' => 'nullable|numeric',
        'formData.persol_price_usd' => 'nullable|numeric',
        'formData.price_difference' => 'nullable|numeric',
        'formData.cfr_china' => 'nullable|numeric',
        'formData.status' => 'nullable|string|max:255',
        'formData.note' => 'nullable|string',
        'formData.transport_type' => 'nullable|string|max:255',
        'formData.transport_cost' => 'nullable|numeric',
        'formData.container_type' => 'nullable|string|max:255',
        'formData.thc_cost' => 'nullable|numeric',
        'formData.stuffing_cost' => 'nullable|numeric',
        'formData.ocean_freight' => 'nullable|numeric',
        'formData.exchange_rate' => 'nullable|numeric',
        'formData.total_cost' => 'nullable|numeric',
        'formData.additional_costs' => 'nullable|array',
        'formData.additional_costs.*.name' => 'required_with:formData.additional_costs.*.cost|nullable|string|max:255',
        'formData.additional_costs.*.cost' => 'required_with:formData.additional_costs.*.name|nullable|numeric',
    ];

    public array $formData = [];


    public function initializeFormData()
    {
        $this->formData = $this->getDefaultFormData();
    }


    protected function getDefaultFormData(): array
    {
        return [
            'product_id' => null,
            'grade_id' => null,
            'supplier_id' => null,
            'packaging_id' => null,
            'tender_no' => null,
            'date' => null,
            'validity' => null,
            'quantity' => null,
            'term' => null,
            'win_price_usd' => null,
            'persol_price_usd' => null,
            'price_difference' => null,
            'cfr_china' => null,
            'status' => null,
            'note' => null,
            'transport_type' => null,
            'transport_cost' => null,
            'container_type' => null,
            'thc_cost' => null,
            'stuffing_cost' => null,
            'ocean_freight' => null,
            'exchange_rate' => null,
            'additional_costs' => [],
            'total_cost' => null,
        ];
    }

    protected function fetchSelectData(): array
    {
        $userId = auth()->id();

        if (!$userId) {
            abort(401, 'Authentication required to access this data.');
        }

        $cacheKey = 'cached_form_select_data_user_' . $userId;

        return Cache::remember($cacheKey, 300, function () { // for 300 seconds
            return [
                'products' => Product::select(['id', 'name'])->orderBy('name')->get(),
                'grades' => Grade::select(['id', 'name', 'product_id'])->orderBy('name')->get(),
                'suppliers' => Supplier::select(['id', 'name'])->orderBy('name')->get(),
                'packaging' => Packaging::select(['id', 'name'])->orderBy('name')->get(),
                'statusOptions' => $this->statusOptions,
                'transportTypeOptions' => $this->transportTypeOptions,
                'containerTypeOptions' => $this->containerTypeOptions,
                'incotermsOptions' => $this->incotermsOptions,
            ];
        });
    }
}


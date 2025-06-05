<?php

namespace App\Livewire\CostCalculation;

use App\Models\CostCalculation as Model;
use App\Services\Traits\CostCalculationData;
use Illuminate\Support\Arr;
use Livewire\Component;


class CostCalculationForm extends Component
{
    use CostCalculationData;

    public function mount()
    {
        $this->loadFreshFormState();
    }

    public function loadFreshFormState(): void
    {
        $this->initializeFormData();

        $selectData = $this->fetchSelectData();
        $this->formData['user_id'] = auth()->id();
        $this->formData['allGrades'] = $selectData['grades'];
        $this->formData['products'] = $selectData['products'];
        $this->formData['grades'] = $this->formData['allGrades'];
        $this->formData['suppliers'] = $selectData['suppliers'];
        $this->formData['packaging'] = $selectData['packaging'];
        $this->formData['statusOptions'] = $selectData['statusOptions'];
        $this->formData['transportTypeOptions'] = $selectData['transportTypeOptions'];
        $this->formData['containerTypeOptions'] = $selectData['containerTypeOptions'];
        $this->formData['incotermsOptions'] = $selectData['incotermsOptions'];
        $this->calculateTotalCost();
    }


    public function productSelected($newProductId): void
    {
        $this->formData['grade_id'] = null;
        $this->formData['grades'] = $this->formData['allGrades']
            ->when(strlen($newProductId ?? ''), fn($c) => $c->where('product_id', $newProductId))
            ->values();
    }

    public function addAdditionalCost()
    {
        $this->formData['additional_costs'][] = ['name' => '', 'cost' => null];
    }

    public function removeAdditionalCost($index): void
    {
        $costs = &$this->formData['additional_costs'];
        isset($costs[$index]) && array_splice($costs, $index, 1) && $this->calculateTotalCost();
    }

    public function calculateTotalCost(): void
    {
        $total = array_sum(array_map(
            fn(string $key): float => (float)($this->formData[$key] ?? 0),
            ['transport_cost', 'exchange_rate', 'thc_cost', 'stuffing_cost', 'ocean_freight']
        ));

        $total += array_sum(array_map(
            fn(array $item): float => (float)($item['cost'] ?? 0),
            (array)($this->formData['additional_costs'] ?? [])
        ));

        $this->formData['total_cost'] = round($total, 2);
    }

    public function saveCostCalculation()
    {

        if ($this->validate()) {
            Model::create(Arr::only($this->formData, [
                'product_id',
                'grade_id',
                'supplier_id',
                'packaging_id',
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
                'total_cost',
                'additional_costs',
                'user_id',
            ]));
            $this->loadFreshFormState();

            $this->dispatch('refreshParent');
            $this->dispatch('notify-success', message: 'Cost Calculation added successfully.');
            return;
        }

        $this->dispatch('notify-error', message: 'Failed to save Cost Calculation: ');
    }

    public function render()
    {
        return view('livewire.cost-calculation.cost-calculation-form');
    }
}

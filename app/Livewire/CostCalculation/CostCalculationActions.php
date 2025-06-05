<?php

namespace App\Livewire\CostCalculation;

use App\Models\CostCalculation;
use App\Services\Traits\CostCalculationData;
use Illuminate\Support\Arr;
use Livewire\Attributes\On;
use Livewire\Component;


class CostCalculationActions extends Component
{
    use CostCalculationData;

    public bool $isEditModalVisible = false;
    public ?CostCalculation $selectedCostCalculation = null;
    public array $formData = [];
    public array $selectData = [];

    protected $listeners = [
        'refreshComponent' => '$refresh',
    ];


    public function mount(): void
    {
        $this->initializeFormData();
        $this->selectData = $this->fetchSelectData();
        $this->selectData['allGrades']= $this->selectData['grades'];
    }

    #[On('open-edit-calculation')]
    public function openEditModal(int $id): void
    {
        $this->selectedCostCalculation = CostCalculation::with([
            'product', 'grade', 'supplier', 'packaging'
        ])->findOrFail($id);

        $this->formData = [
            'product_id' => $this->selectedCostCalculation->product_id,
            'grade_id' => $this->selectedCostCalculation->grade_id,
            'supplier_id' => $this->selectedCostCalculation->supplier_id,
            'packaging_id' => $this->selectedCostCalculation->packaging_id,
            'tender_no' => $this->selectedCostCalculation->tender_no,
            'date' => optional($this->selectedCostCalculation->date)?->format('Y-m-d'),
            'validity' => optional($this->selectedCostCalculation->validity)?->format('Y-m-d'),
            'quantity' => $this->selectedCostCalculation->quantity,
            'term' => $this->selectedCostCalculation->term,
            'win_price_usd' => $this->selectedCostCalculation->win_price_usd,
            'persol_price_usd' => $this->selectedCostCalculation->persol_price_usd,
            'price_difference' => $this->selectedCostCalculation->price_difference,
            'cfr_china' => $this->selectedCostCalculation->cfr_china,
            'status' => $this->selectedCostCalculation->status,
            'note' => $this->selectedCostCalculation->note,
            'transport_type' => $this->selectedCostCalculation->transport_type,
            'transport_cost' => $this->selectedCostCalculation->transport_cost,
            'container_type' => $this->selectedCostCalculation->container_type,
            'thc_cost' => $this->selectedCostCalculation->thc_cost,
            'stuffing_cost' => $this->selectedCostCalculation->stuffing_cost,
            'ocean_freight' => $this->selectedCostCalculation->ocean_freight,
            'exchange_rate' => $this->selectedCostCalculation->exchange_rate,
            'total_cost' => $this->selectedCostCalculation->total_cost,
            'additional_costs' => $this->selectedCostCalculation->additional_costs ?? [],
        ];

        $this->isEditModalVisible = true;
    }

    public function updateCostCalculation(): void
    {
        $this->validate();

        $this->selectedCostCalculation->update(
            Arr::only($this->formData, [
                'product_id', 'grade_id', 'supplier_id', 'packaging_id',
                'tender_no', 'date', 'validity', 'quantity', 'term',
                'win_price_usd', 'persol_price_usd', 'price_difference', 'cfr_china',
                'status', 'note', 'transport_type', 'transport_cost',
                'container_type', 'thc_cost', 'stuffing_cost', 'ocean_freight',
                'exchange_rate', 'total_cost', 'additional_costs',
            ])
        );

        $this->dispatch('notify-success', message: 'Cost calculation updated successfully.');
        $this->dispatch('refreshComponent');
        $this->dispatch('refreshParent');
        $this->closeEditModal();
    }

    #[On('closeEditForm')]
    public function closeEditModal(): void
    {
        $this->isEditModalVisible = false;
        $this->selectedCostCalculation = null;
        $this->initializeFormData();
    }

    #[On('confirmDeleteCostCalculation')]
    public function deleteCostCalculation(int $id): void
    {
        CostCalculation::findOrFail($id)->delete();

        $this->dispatch('notify-success', message: 'Cost calculation deleted successfully.');
        $this->dispatch('refreshComponent');
        $this->dispatch('refreshParent');
    }

    public function addAdditionalCost(): void
    {
        $this->formData['additional_costs'][] = ['name' => '', 'cost' => null];
    }

    public function removeAdditionalCost(int $index): void
    {
        unset($this->formData['additional_costs'][$index]);
        $this->formData['additional_costs'] = array_values($this->formData['additional_costs']);
    }

    public function calculateTotalCost(): void
    {
        $total = 0;

        foreach (['transport_cost', 'exchange_rate', 'thc_cost', 'stuffing_cost', 'ocean_freight'] as $field) {
            if (isset($this->formData[$field]) && is_numeric($this->formData[$field])) {
                $total += $this->formData[$field];
            }
        }

        foreach ($this->formData['additional_costs'] ?? [] as $item) {
            if (is_numeric($item['cost'] ?? null)) {
                $total += $item['cost'];
            }
        }

        $this->formData['total_cost'] = round($total, 2);
    }

    public function filterGrade(): void
    {
        $this->formData['grade_id'] = null;
        $this->selectData['grades'] = $this->selectData['allGrades']->where('product_id', $this->formData['product_id']);
    }

    public function render()
    {

        return view('livewire.cost-calculation.cost-calculation-actions', [
            'products' => $this->selectData['products'],
            'allGrades' => $this->selectData['grades'],
            'grades' => $this->selectData['grades'],
            'suppliers' => $this->selectData['suppliers'],
            'packaging' => $this->selectData['packaging'],
            'statusOptions' => $this->selectData['statusOptions'],
            'transportTypeOptions' => $this->selectData['transportTypeOptions'],
            'containerTypeOptions' => $this->selectData['containerTypeOptions'],
            'incotermsOptions' => $this->selectData['incotermsOptions'],
        ]);
    }
}

<?php

namespace App\Livewire\CostCalculation;

use App\Models\CostCalculation;
use App\Services\Traits\CostCalculationData;
use Illuminate\Support\Str;
use Livewire\Component;
use Livewire\WithPagination;


class CostCalculationTable extends Component
{
    use WithPagination, CostCalculationData;

    public $search = '';
    public $sortField = 'date';
    public $sortDirection = 'desc';
    public $products = [];
    public $suppliers = [];
    public $grades = [];
    public $packaging = [];
    public $isViewModalVisible = false;
    public $selectedRecord = null;
    public $perPage = 10;


    public $filters = [
        'product_id' => '',
        'supplier_id' => '',
        'status' => '',
        'date_from' => '',
        'date_to' => '',
        'transport_type' => '',
        'container_type' => '',
        'incoterms' => '',
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'sortField' => ['except' => 'date'],
        'sortDirection' => ['except' => 'desc'],
        'filters' => ['except' => [
            'product_id' => '',
            'supplier_id' => '',
            'status' => '',
            'date_from' => '',
            'date_to' => '',
            'transport_type' => '',
        ]],
        'perPage' => ['except' => 10],
    ];

    protected $listeners = [
        'refreshParent' => '$refresh',
    ];


    public function mount()
    {
        $this->initializeFormData();
        $this->loadFilterOptions();
    }

    public function loadFilterOptions()
    {
        $data = $this->fetchSelectData();

        $this->products = $data['products'];
        $this->suppliers = $data['suppliers'];
        $this->grades = $data['grades'];
        $this->packaging = $data['packaging'];
        $this->transportTypeOptions = $this->processTypeOptions($data['transportTypeOptions']);
        $this->containerTypeOptions = $this->processTypeOptions($data['containerTypeOptions']);
    }


    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedFilters()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->reset('filters', 'search');
        $this->resetPage();
    }

    public function sortBy($field)
    {
        $this->sortDirection = $this->sortField === $field && $this->sortDirection === 'asc'
            ? 'desc'
            : 'asc';
        $this->sortField = $field;
    }

    public function showDetails($id)
    {
        $this->selectedRecord = CostCalculation::with(['product', 'grade', 'supplier', 'packaging'])
            ->findOrFail($id);
        $this->isViewModalVisible = true;
    }

    public function closeDetails()
    {
        $this->isViewModalVisible = false;
        $this->selectedRecord = null;
    }

    protected function processTypeOptions(array $typeOptions): array
    {
        return collect($typeOptions)
            ->flatMap(fn($types, $category) => array_combine($types, array_fill(0, count($types), "$category: ")))
            ->mapWithKeys(fn($value, $key) => [$key => "$value$key"])
            ->toArray();
    }

    public function render()
    {
        $searchRelations = ['product', 'supplier'];
        $searchFields = ['tender_no', 'term', 'transport_type', 'container_type', 'status'];

        $filterHandlers = [
            'date_from' => fn($q, $value) => $q->whereDate('date', '>=', $value),
            'date_to' => fn($q, $value) => $q->whereDate('date', '<=', $value),
            'incoterms' => fn($q, $value) => $q->where('term', $value),
        ];

        $query = CostCalculation::with(['product', 'grade', 'supplier', 'packaging'])
            ->when($this->search, function ($q) use ($searchRelations, $searchFields) {
                $q->where(function ($q) use ($searchRelations, $searchFields) {
                    foreach ($searchRelations as $rel) {
                        $q->orWhereHas($rel, function ($q) {
                            $q->where('name', 'like', "%{$this->search}%");
                        });
                    }
                    foreach ($searchFields as $field) {
                        $q->orWhere($field, 'like', "%{$this->search}%");
                    }
                });
            })
            ->when(array_filter($this->filters), function ($q) use ($filterHandlers) {
                collect($this->filters)->filter()->each(function ($value, $key) use ($q, $filterHandlers) {
                    $handler = $filterHandlers[$key] ?? fn($q, $value) => $q->where($key, $value);
                    $handler($q, $value);
                });
            })
            ->when(str_ends_with($this->sortField, '_id'), function ($q) {
                $table = Str::plural(Str::beforeLast($this->sortField, '_id'));
                $q->join($table, "$table.id", "cost_calculations.{$this->sortField}")
                    ->orderBy("$table.name", $this->sortDirection)
                    ->select('cost_calculations.*');
            }, fn($q) => $q->orderBy($this->sortField, $this->sortDirection));

        $costCalculations = $query->paginate($this->perPage);

        return view('livewire.cost-calculation.cost-calculation-table', [
            'costCalculations' => $costCalculations
        ]);
    }
}

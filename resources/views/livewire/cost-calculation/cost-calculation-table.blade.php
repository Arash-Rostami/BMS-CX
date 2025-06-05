<div>
    <!-- Filter and Search Section -->
    <div class="content-wrapper alternative rounded-lg shadow p-4">
        @include('components.CostCalculation.table-header')
    </div>

    <!-- Results Table -->
    <div class="overflow-hidden">
        @include('components.CostCalculation.table-body')
        <div class="table-pagination">
            @include('components.CostCalculation.pagination', ['paginator' => $costCalculations])
        </div>
    </div>

    <!-- Modals: Edit/Delete | View | Confirmation -->
    @livewire('cost-calculation.cost-calculation-actions')
    @include('components.CostCalculation.table-view')
    @include('components.CostCalculation.confirmation')
</div>

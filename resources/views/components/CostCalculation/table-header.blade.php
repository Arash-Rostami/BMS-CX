<!-- Icons -->
<div class="flex flex-col md:flex-row justify-between items-center mb-4 p-2">
    <h3 class="text-lg font-semibold mb-2 md:mb-0">
        <span class="material-icons-outlined align-middle mr-1 insight">filter_list</span>
        Filter Options
    </h3>
    <div class="flex items-center space-x-2">
        <button wire:click="resetFilters"
                class="px-3 py-1 text-sm main-color-button rounded hover:bg-gray-300 dark:hover:bg-gray-600 transition">
            <span class="material-icons-outlined text-sm align-middle">restart_alt</span>
            Reset
        </button>
    </div>
</div>
<!-- Inputs -->
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4 p-4">
    <!-- Search -->
    <div class="col-span-1 md:col-span-2 lg:col-span-3 xl:col-span-4">
        <div class="relative">
            <label class="block text-sm font-medium label-text mb-2">
                <span class="material-icons-outlined text-gray-500">search</span>
                Search
            </label>

            <input
                type="text"
                wire:model.live.debounce.300ms="search"
                class="form-input pl-10 w-full"
                placeholder="Search by product, supplier, tender no, or status..."
            >
        </div>
    </div>
    <!-- Product Filter -->
    <div>
        <label class="block text-sm font-medium label-text mb-2">
            <span class="material-icons-outlined text-sm text-gray-500">shopping_cart</span>
            Product
        </label>
        <select wire:model.live="filters.product_id" class="form-input">
            <option value="">All Products</option>
            @foreach($products as $product)
                <option value="{{ $product->id }}">{{ $product->name }}</option>
            @endforeach
        </select>
    </div>
    <!-- Supplier Filter -->
    <div>
        <label class="block text-sm font-medium label-text mb-2">
            <span class="material-icons-outlined text-sm text-gray-500">business</span>
            Supplier
        </label>
        <select wire:model.live="filters.supplier_id" class="form-input">
            <option value="">All Suppliers</option>
            @foreach($suppliers as $supplier)
                <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
            @endforeach
        </select>
    </div>
    <!-- Incoterms Filter -->
    <div>
        <label class="block text-sm font-medium label-text mb-2">
            <span class="material-icons-outlined text-sm text-gray-500">event</span>
            Incoterms
        </label>
        <select wire:model.live="filters.incoterms" class="form-input">
            <option value="">All Incoterms</option>
            @foreach($incotermsOptions as $value )
                <option value="{{ $value }}">{{ $value }}</option>
            @endforeach
        </select>
    </div>
    <!-- Transport Type Filter -->
    <div>
        <label class="block text-sm font-medium label-text mb-2">
            <span class="material-icons-outlined text-sm text-gray-500">local_shipping</span>
            Transport Type
        </label>
        <select wire:model.live="filters.transport_type" class="form-input">
            <option value="">All Transport Types</option>
            @foreach($transportTypeOptions as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <!-- Contrainer Type Filter -->
    <div>
        <label class="block text-sm font-medium label-text mb-2">
            <span class="material-icons-outlined text-sm text-gray-500">local_shipping</span>
            Container Type
        </label>
        <select wire:model.live="filters.container_type" class="form-input">
            <option value="">All Container Types</option>
            @foreach($containerTypeOptions as $value => $label)
                <option value="{{ $value }}">{{ $label }}</option>
            @endforeach
        </select>
    </div>
    <!-- Status Filter -->
    <div>
        <label class="block text-sm font-medium label-text mb-2">
            <span class="material-icons-outlined text-sm text-gray-500">check_circle_outline</span>
            Status
        </label>
        <select wire:model.live="filters.status" class="form-input">
            <option value="">All Statuses</option>
            @foreach($statusOptions as $status)
                <option value="{{ $status }}">{{ $status }}</option>
            @endforeach
        </select>
    </div>
    <!-- Date From Filter -->
    <div>
        <label class="block text-sm font-medium label-text mb-2">
            <span class="material-icons-outlined text-sm text-gray-500">event</span>
            Date From
        </label>
        <input type="date" wire:model.live="filters.date_from" class="form-input">
    </div>
    <!-- Date To Filter -->
    <div>
        <label class="block text-sm font-medium label-text mb-2">
            <span class="material-icons-outlined text-sm text-gray-500">event</span>
            Date To
        </label>
        <input type="date" wire:model.live="filters.date_to" class="form-input">
    </div>
    <!-- Per Page Selector -->
    <div>
        <label class="block text-sm font-medium label-text mb-2">
            <span class="material-icons-outlined text-sm text-gray-500">format_list_numbered</span>
            Records Per Page
        </label>
        <select wire:model.live="perPage" class="form-input">
            <option value="10">10</option>
            <option value="25">25</option>
            <option value="50">50</option>
            <option value="100">100</option>
        </select>
    </div>
</div>

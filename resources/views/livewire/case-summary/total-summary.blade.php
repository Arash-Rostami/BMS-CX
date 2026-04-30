<div class="main-container"
     x-data="{ activeTab: 'proforma-summary' }"
     @reset-active-tab.window="activeTab = 'proforma-summary'">
    <div class="w-full mb-5 flex justify-end">
        <button
            id="dark-mode-toggle"
            class="px-2 py-2 rounded my-dark-class hover:bg-gray-300 w-auto flex items-center justify-center z-10">
            <span id="dark-mode-icon" class="material-icons-outlined">brightness_7</span>
        </button>
    </div>
    <div class="content-wrapper">
        <div class="search-container mb-6 flex flex-col gap-4">
            <!-- Search -->
            <div class="relative w-full">
                <input
                    type="search"
                    wire:model.live.debounce.500ms="search"
                    placeholder="Search by Proforma, Contract, Reference, Buyer, Supplier, ..."
                    class="search-input w-full pr-10"
                />

                <div wire:loading wire:target="search" class="spinner-container">
                    <div class="spinner"></div>
                </div>

                @if (!empty($proformaOptions))
                    <ul class="search-results">
                        @foreach($proformaOptions as $option)
                            <li
                                wire:click="selectProforma({{ $option->id }})"
                                class="search-result-item"
                            >
                                <div class="flex items-center">
                                    <span class="material-icons-outlined mr-2">search</span>
                                    <div class="flex flex-col">
                                <span class="font-semibold" title="Proforma Invoice No.">
                                    {{ $option->proforma_number }}
                                </span>
                                        <div class="text-gray-500">
                                            ➟
                                            <span title="Contract No.">
                                        {{ $option->contract_number ?? 'No CT No.' }}
                                    </span>
                                            <span class="mx-1">┆</span>
                                            <span title="Reference No.">
                                        {{ $option->reference_number ?? 'No Ref. No.' }}
                                    </span>
                                            <span class="mx-1">┆</span>
                                            <span title="Supplier Name">
                                        {{ $option->supplier->name ?? 'No Supplier' }}
                                    </span>
                                        </div>
                                    </div>
                                </div>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </div>

            <!-- Filter -->
            <div class="contract-filter-shell transition-all duration-200 {{ $selectedProforma ? 'opacity-100' : 'opacity-0 pointer-events-none select-none' }}">
                <span class="material-icons-outlined contract-filter-icon">filter_list</span>

                <input
                    type="search"
                    id="contract-filter"
                    placeholder="Filter contract details..."
                    class="contract-filter-input"
                >

                <button
                    type="button"
                    id="contract-filter-clear"
                    class="contract-filter-clear hidden"
                    aria-label="Clear filter"
                >
                    ✕
                </button>
            </div>
        </div>
    </div>

    @if ($selectedProforma)
        <!-- Verification Section -->
        <div class="pt-4 mb-4 flex items-center cursor-pointer"
             title=" @if($selectedProforma->verified) Reviewed. Further edits will require re-verification. @else Awaiting review and verification. @endif">
            @if($selectedProforma->verified)
                <span class="material-icons-outlined text-green-500 mr-2">check_circle</span>
                <span class="text-green-500 font-semibold mr-2">Verified</span>
                <span class="text-sm text-gray-600">
                        on {{ $selectedProforma->verified_at ? $selectedProforma->verified_at->format('M d, Y h:i A') : '' }}
                    @if($selectedProforma->verifier)
                        by {{ $selectedProforma->verifier->full_name }}
                    @endif
                    </span>
            @else
                <button wire:click="verifyProforma"
                        class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
                    Verify
                </button>
            @endif
        </div>
        <!-- Tabs Navigation -->
        <ul class="nav nav-tabs flex border-b">
            <li class="nav-item">
                <button class="text-md md:text-lg font-semibold tracking-wide"
                        @click="activeTab = 'proforma-summary'"
                        :class="activeTab === 'proforma-summary'
                    ? 'nav-link bg-blue-500 text-white'
                    : 'nav-link bg-gray-200'">
                    Contract Summary
                </button>
            </li>
            <li class="nav-item">
                <button class="text-md md:text-lg font-semibold tracking-wide"
                        @click="activeTab = 'financial-summary'; $wire.loadFinancialTab()" :class="activeTab === 'financial-summary'
                    ? 'nav-link bg-blue-500 text-white'
                    : 'nav-link bg-gray-200'">
                    Financial Summary
                </button>
            </li>
            <li class="nav-item">
                <button class="text-md md:text-lg font-semibold tracking-wide"
                        @click="activeTab = 'supplier-summary'; $wire.loadSupplierTab()"
                        :class="activeTab === 'supplier-summary'
                    ? 'nav-link bg-blue-500 text-white'
                    : 'nav-link bg-gray-200'">
                    Supplier Summary
                </button>
            </li>
        </ul>

    @endif

    <!-- Tabs Content -->
    <div class="tab-content">
        <!-- Display Summary: Proforma Invoice | Order(s) | Payment(s) | Attachment(s) -->
        <div x-show="activeTab === 'proforma-summary'" class="tab-pane p-0">
            @if ($selectedProforma)
                <div class="proforma-details-container">@include('components.Summary.business-insights')</div>
                <div class="proforma-details-container">@include('components.Summary.proforma-invoice')</div>
                <div class="proforma-details-container">@include('components.Summary.orders')</div>
                <div class="proforma-details-container">@include('components.Summary.payments')</div>
            @else
                <div class="empty-state"><span class="material-icons-outlined">search</span>
                    <p>Search for details and select a proforma invoice to view the case summary.</p>
                </div>
            @endif
        </div>
        @if ($selectedProforma)
            <!-- Display Summary: Financial Departments Concise Insight -->
            <div x-show="activeTab === 'financial-summary'" class="tab-pane p-0">
                @if($loadFinancialSummary)
                    <div class="livewire-financial-summary">
                        @livewire(
                        'case-summary.financial-summary',
                        [ 'proformaInvoice' => $selectedProforma],
                        key("financial-summary-{$selectedProforma->id}")
                        )
                    </div>
                @else
                    <div class="loader-placeholder">
                        <div class="loader-gradient"></div>
                    </div>
                @endif
            </div>
            <!-- Display Summary: Supplier Balance -->
            <div x-show="activeTab === 'supplier-summary'" class="tab-pane p-0">
                @if($loadSupplierSummary)
                    <div class="livewire-supplier-summary">
                        @livewire(
                        'case-summary.supplier-summary',
                        ['supplierId' => $selectedProforma->supplier_id ?? null],
                        key("supplier-summary-{$selectedProforma->supplier_id}")
                        )
                    </div>
                @else
                    <div class="loader-placeholder">
                        <div class="loader-gradient"></div>
                    </div>
                @endif
            </div>
        @endif
    </div>
    {{--    <!-- AI Assistant -->--}}
    <x-Summary.ai></x-Summary.ai>
</div>


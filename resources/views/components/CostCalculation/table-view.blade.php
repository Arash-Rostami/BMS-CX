<div
    x-data="{
        open: @entangle('isViewModalVisible')
    }"
    x-show="open"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0 scale-95"
    x-transition:enter-end="opacity-100 scale-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100 scale-100"
    x-transition:leave-end="opacity-0 scale-95"
    class="fixed inset-0 flex items-center justify-center z-50 overflow-scroll"
    style="display: none;"
>
    <div class="relative content-wrapper rounded-lg shadow-xl max-w-4xl w-full mx-4 max-h-screen overflow-y-auto">
        @if(isset($selectedRecord) && $selectedRecord)
            <div class="p-6">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <div>
                        <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-3">Basic Information</h4>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="label-text">Product:</span>
                                <span class="font-medium">{{ $selectedRecord->product->name ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="label-text">Grade:</span>
                                <span class="font-medium">{{ $selectedRecord->grade->name ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="label-text">Supplier:</span>
                                <span class="font-medium">{{ $selectedRecord->supplier->name ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="label-text">Packaging:</span>
                                <span class="font-medium">{{ $selectedRecord->packaging->name ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="label-text">Tender No:</span>
                                <span class="font-medium">{{ $selectedRecord->tender_no ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="label-text">Date:</span>
                                <span class="font-medium">{{ $selectedRecord->date?->format('Y-m-d') ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="label-text">Validity:</span>
                                <span
                                    class="font-medium">{{ $selectedRecord->validity?->format('Y-m-d') ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="label-text">Quantity:</span>
                                <span class="font-medium">
                                {{ $selectedRecord->quantity
                                    ? number_format($selectedRecord->quantity, 2).' MT'
                                    : '-' }}
                            </span>
                            </div>
                            <div class="flex justify-between">
                                <span class="label-text">Term:</span>
                                <span class="font-medium">{{ $selectedRecord->term ?? '-' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="label-text">Status:</span>
                                <span class="font-medium">{{ $selectedRecord->status ?? '-' }}</span>
                            </div>
                        </div>
                    </div>

                    <div>
                        <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-3">Pricing & Costs</h4>
                        <div class="space-y-2">
                            <div class="flex justify-between">
                                <span class="label-text">Win Price (USD):</span>
                                <span class="font-medium">{{ $selectedRecord->win_price_usd
                                    ? '$'.number_format($selectedRecord->win_price_usd,2)
                                    : '-' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="label-text">Persol Price (USD):</span>
                                <span class="font-medium">{{ $selectedRecord->persol_price_usd
                                    ? '$'.number_format($selectedRecord->persol_price_usd,2)
                                    : '-' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="label-text">Price Difference:</span>
                                <span class="font-medium">{{ $selectedRecord->price_difference
                                    ? '$'.number_format($selectedRecord->price_difference,2)
                                    : '-' }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="label-text">CFR China (USD):</span>
                                <span class="font-medium">{{ $selectedRecord->cfr_china
                                    ? '$'.number_format($selectedRecord->cfr_china,2)
                                    : '-' }}</span>
                            </div>

                            @if(is_array($selectedRecord->additional_costs))
                                @foreach($selectedRecord->additional_costs as $item)
                                    <div class="flex justify-between">
                                        <span class="label-text">{{ $item['name'] }}</span>
                                        <span class="font-medium">
                                        ${{ number_format((float)$item['cost'],2) }}
                                    </span>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>

                    <div class="md:col-span-2">
                        <h4 class="font-semibold text-gray-700 dark:text-gray-300 mb-3">
                            Transport & Delivery Information
                        </h4>
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="label-text">Transport Type:</span>
                                    <span class="font-medium">{{ $selectedRecord->transport_type ?? '-' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="label-text">Container Type:</span>
                                    <span class="font-medium">{{ $selectedRecord->container_type ?? '-' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="label-text">THC Cost (USD):</span>
                                    <span class="font-medium">{{ $selectedRecord->thc_cost
                                        ? '$'.number_format($selectedRecord->thc_cost,2)
                                        : '-' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="label-text">Transport Cost (USD):</span>
                                    <span class="font-medium">{{ $selectedRecord->transport_cost
                                        ? '$'.number_format($selectedRecord->transport_cost,2)
                                        : '-' }}</span>
                                </div>
                            </div>
                            <div class="space-y-2">
                                <div class="flex justify-between">
                                    <span class="label-text">Stuffing Cost (USD):</span>
                                    <span class="font-medium">{{ $selectedRecord->stuffing_cost
                                        ? '$'.number_format($selectedRecord->stuffing_cost,2)
                                        : '-' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="label-text">Ocean Freight (USD):</span>
                                    <span class="font-medium">{{ $selectedRecord->ocean_freight
                                        ? '$'.number_format($selectedRecord->ocean_freight,2)
                                        : '-' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="label-text">Exchange Rate:</span>
                                    <span class="font-medium">{{ $selectedRecord->exchange_rate
                                        ? '$' .number_format($selectedRecord->exchange_rate,2)
                                        : '-' }}</span>
                                </div>
                                <div class="flex justify-between">
                                    <span class="label-text">Total Cost (USD):</span>
                                    <span class="font-medium">{{ $selectedRecord->total_cost
                                    ? '$'.number_format($selectedRecord->total_cost,2)
                                    : '-' }}</span>
                                </div>
                            </div>
                        </div>
                    </div>

                    @if($selectedRecord->note)
                        <div class="md:col-span-2">
                            <h4 class="font-semibold mb-3">Note</h4>
                            <div class="note-box">
                                {{ $selectedRecord->note }}
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            <div class="p-4 border-t border-gray-200 dark:border-gray-700 flex justify-end space-x-3">
                <button
                    wire:click="delete({{ $selectedRecord->id }})"
                    wire:confirm="Are you sure?"
                    class="px-4 py-2 btn-delete"
                >
                    <span class="material-icons-outlined text-sm align-middle mr-1">delete</span>
                    Delete
                </button>
                <button wire:click="closeDetails" class="px-4 py-2">
                    <span class="material-icons-outlined text-sm align-middle mr-1">close</span>
                    Close
                </button>
            </div>
        @endif
    </div>
</div>

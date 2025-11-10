<!-- Accordion Container -->
<div x-data="{ open: true }" class="border rounded-lg shadow-lg mb-4">
    <button @click="open = !open"
            class="w-full flex justify-between items-center transition-colors px-4 py-3 text-left text-lg md:text-2xl font-semibold rounded-t-lg">
        <span><span
                class="material-icons-outlined text-lg md:text-2xl insight">insights</span> Financial Insights</span>
        <span class="material-icons-outlined" x-show="open">expand_less</span>
        <span class="material-icons-outlined" x-show="!open">expand_more</span>
    </button>

    <!-- Accordion Content -->
    <div x-show="open" x-collapse class="p-4 border-t">
        <div class="proforma-details-grid grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div class="proforma-details-box bg-white p-4 rounded-lg shadow-sm cursor-help">
                <div class="font-medium flex items-center space-x-2">
                    <span class="material-icons-outlined text-gray-500">list_alt</span>
                    <span>Related Records:</span>
                </div>
                <x-Summary.tooltip
                    tooltip="Total count of related records linked to this contract."
                >
                    <pre class="text-lg font-bold">{!! $businessInsights->total_count ?? 'N/A' !!}</pre>
                </x-Summary.tooltip>
            </div>
            <div class="proforma-details-box bg-white p-4 rounded-lg shadow-sm cursor-help">
                <div class="font-medium flex items-center space-x-2">
                    <span class="material-icons-outlined text-gray-500">account_balance_wallet </span>
                    <span>Contract Value:</span>
                </div>
                <x-Summary.tooltip
                    tooltip="Advance and remaining balances based on agreed-upon contractual price, quantities, and percentages."
                >
                    <pre class="text-lg font-bold">Advance: ${{ number_format($businessInsights->contractual_prepayment, 2) }}  Remaining: ${{ number_format($businessInsights->contractual_remaining_amount, 2) }}</pre>
                </x-Summary.tooltip>
            </div>
            <div class="proforma-details-box bg-white p-4 rounded-lg shadow-sm cursor-help">
                <div class="font-medium flex items-center space-x-2">
                    <span class="material-icons-outlined text-gray-500">attach_money</span>
                    <span>Initial Aggregate:</span>
                </div>
                <x-Summary.tooltip
                    tooltip="The cumulative initial payment requests across all orders, according to quantity and price set in orders."
                >
                    <pre
                        class="text-lg font-bold">${{ number_format($businessInsights->total_initial_payment ?? 0, 2) }}</pre>
                </x-Summary.tooltip>

            </div>
            <div class="proforma-details-box bg-white p-4 rounded-lg shadow-sm cursor-help">
                <div class="font-medium flex items-center space-x-2">
                    <span class="material-icons-outlined text-gray-500">show_chart</span>
                    <span>Provisional Aggregate:</span>
                </div>
                <x-Summary.tooltip
                    tooltip="The estimated total value across all orders, subject to final adjustments."
                >
                     <pre
                         class="text-lg font-bold">${{ number_format($businessInsights->total_provisional_total ?? 0, 2) }}</pre>
                </x-Summary.tooltip>
            </div>
            <div class="proforma-details-box bg-white p-4 rounded-lg shadow-sm cursor-help">
                <div class="font-medium flex items-center space-x-2">
                    <span class="material-icons-outlined text-gray-500">check_circle</span>
                    <span>Final Aggregate:</span>
                </div>
                <x-Summary.tooltip
                    tooltip="The confirmed and conclusive total value for all orders after all adjustments."
                >
                    <pre
                        class="text-lg font-bold">${{ number_format($businessInsights->total_final_total ?? 0, 2) }}</pre>
                </x-Summary.tooltip>
            </div>
            <div class="proforma-details-box bg-white p-4 rounded-lg shadow-sm cursor-help">
                <div class="font-medium flex items-center space-x-2">
                    <span class="material-icons-outlined text-gray-500">monetization_on</span>
                    <span>Total Aggregate:</span>
                </div>
                <x-Summary.tooltip
                    tooltip="The combined total of all initial, provisional, and final amounts across all orders."
                >
                    <pre
                        class="text-lg font-bold">${{ number_format($businessInsights->aggregate_total ?? 0, 2) }}</pre>
                </x-Summary.tooltip>
            </div>
            <div class="proforma-details-box bg-white p-4 rounded-lg shadow-sm cursor-help">
                <div class="font-medium flex items-center space-x-2">
                    <span class="material-icons-outlined text-gray-500">monetization_on</span>
                    <span>Total Payment Requests:</span>
                </div>
                <x-Summary.tooltip
                    tooltip="The cumulative number of payment requests that have been made by BMS users."
                >
                    <pre class="text-lg font-bold">{!! $businessInsights->total_payment_requests_paid ?? 'N/A' !!}</pre>
                </x-Summary.tooltip>
            </div>
            <div class="proforma-details-box bg-white p-4 rounded-lg shadow-sm cursor-help">
                <div class="font-medium flex items-center space-x-2">
                    <span class="material-icons-outlined text-gray-500">payments</span>
                    <span>Total Payments:</span>
                </div>
                <x-Summary.tooltip
                    tooltip="The total sum of all financial payments made."
                >
                    <pre class="text-lg font-bold">{!! $businessInsights->total_payments_paid ?? 'N/A' !!}</pre>
                </x-Summary.tooltip>
            </div>
            <div class="proforma-details-box bg-white p-4 rounded-lg shadow-sm">
                <div class="font-medium flex items-center space-x-2">
                    <span class="material-icons-outlined text-gray-500">event_note</span>
                    <span>BL ↔ Proforma Gap:</span>
                </div>
                <x-Summary.tooltip
                    tooltip="The timing gap/lead between the Bill of Lading (BL) date and the Proforma Invoice date."
                >
                    <pre class="text-lg font-bold">{!! $businessInsights->gap_bl_proforma ?? 'N/A' !!}</pre>
                </x-Summary.tooltip>
            </div>
            <div class="proforma-details-box bg-white p-4 rounded-lg shadow-sm cursor-help">
                <div class="font-medium flex items-center space-x-2">
                    <span class="material-icons-outlined text-gray-500">event_note</span>
                    <span>Declaration ↔ Proforma Gap:</span>
                </div>
                <x-Summary.tooltip
                    tooltip="The time gap/lead between the formal Declaration date and the Proforma Invoice date."
                >
                    <pre class="text-lg font-bold">{!! $businessInsights->gap_declaration_proforma ?? 'N/A' !!}</pre>
                </x-Summary.tooltip>
            </div>

            <div class="proforma-details-box bg-white p-4 rounded-lg shadow-sm cursor-help">
                <div class="font-medium flex items-center space-x-2">
                    <span class="material-icons-outlined text-gray-500">inventory_2</span>
                    <span>Quantity Progress:</span>
                </div>
                <x-Summary.tooltip
                    tooltip="The percentage of the total ordered quantity that has been completed or delivered."
                >
                    <div class="relative w-full bg-gray-300 rounded-full h-4 mt-2 overflow-hidden">
                        <div class="absolute top-0 left-0 h-4 bg-blue-500"
                             style="width: {{ $businessInsights->progress_by_quantity }}%;"></div>
                        <div class="absolute top-0 left-0 h-4 bg-red-400" style="width: 100%; opacity: 0.3;"></div>
                    </div>
                    <div class="text-center font-bold text-sm mt-1">
                        {{ number_format($businessInsights->progress_by_quantity, 2) }}%
                    </div>
                </x-Summary.tooltip>
            </div>
            <div class="proforma-details-box bg-white p-4 rounded-lg shadow-sm cursor-help">
                <div class="font-medium flex items-center space-x-2">
                    <span class="material-icons-outlined text-gray-500">monetization_on</span>
                    <span>Payment Progress:</span>
                </div>
                <x-Summary.tooltip
                    tooltip="The percentage of the total expected payment that has been received."
                >
                    <div class="relative w-full bg-gray-300 rounded-full h-4 mt-2 overflow-hidden">
                        <div class="absolute top-0 left-0 h-4 bg-green-500"
                             style="width: {{ $businessInsights->progress_by_payment }}%;"></div>
                        <div class="absolute top-0 left-0 h-4 bg-red-400" style="width: 100%; opacity: 0.3;"></div>
                    </div>
                    <div class="text-center font-bold text-sm mt-1">
                        {{ number_format($businessInsights->progress_by_payment, 2) }}%
                    </div>
                </x-Summary.tooltip>
            </div>

            <div class="proforma-details-box bg-white p-4 rounded-lg shadow-sm cursor-help">
                <x-Summary.tooltip
                    tooltip="Compares the net weight in orders against the contractually agreed quantity."
                >
                    <div class="font-medium flex items-center space-x-2">
                        <span class="material-icons-outlined text-yellow-500">compare_arrows</span>
                        <span>Quantity Discrepancy:</span>
                    </div>
                </x-Summary.tooltip>
                @if (!empty($businessInsights->quantity_comparison))
                    <div class="mt-2">
                        <pre><span>MT: </span>@if (str_contains($businessInsights->quantity_comparison['status'], 'Over-Ordered'))
                                <span class="text-red-500">{{ $businessInsights->quantity_comparison['status'] }}</span>
                            @elseif (str_contains($businessInsights->quantity_comparison['status'], 'Under-Ordered'))
                                <span
                                    class="text-green-500">{{ $businessInsights->quantity_comparison['status'] }}</span>
                            @else
                                <span
                                    class="text-green-500">{{ $businessInsights->quantity_comparison['status'] }}</span>
                            @endif</pre>
                    </div>
                @else
                    <pre class="text-green-600">No discrepancies found.</pre>
                @endif
            </div>
            <div class="proforma-details-box bg-white p-4 rounded-lg shadow-sm cursor-help">
                <x-Summary.tooltip
                    tooltip="Compares the Total Aggregate against the Total Payment Requests."
                >
                    <div class="font-medium flex items-center space-x-2">
                        <span class="material-icons-outlined text-yellow-500">compare_arrows</span>
                        <span>Order Discrepancy:</span>
                    </div>

                    @if (isset($businessInsights->order_discrepancies))
                        <div class="mt-2 bold">
            <pre><span>USD:</span>@if ($businessInsights->order_discrepancies < 0)
                    <span
                        class="text-green-500"> Under-Requested {{ number_format($businessInsights->order_discrepancies, 2) }}</span>
                @elseif ($businessInsights->order_discrepancies > 0)
                    <span
                        class="text-red-600"> Over-Requested {{ number_format(abs($businessInsights->order_discrepancies), 2) }}</span>
                @else
                    <span class="text-green-500"> Settled</span>
                @endif</pre>
                        </div>
                    @else
                        <pre class="text-gray-600">No discrepancies found.</pre>
                    @endif
                </x-Summary.tooltip>
            </div>
            <div class="proforma-details-box bg-white p-4 rounded-lg shadow-sm cursor-help">
                <x-Summary.tooltip
                    tooltip="Discrepancies between the Total Payment Requests and Total Payments."
                >
                    <div class="font-medium flex items-center space-x-2">
                        <span class="material-icons-outlined text-yellow-500">compare_arrows</span>
                        <span>Payment Request Discrepancy:</span>
                    </div>
                    @if (!empty($businessInsights->payment_discrepancies))

                        <div class="mt-2 bold">
                            @foreach ($businessInsights->payment_discrepancies as $currency => $details)
                                <pre><span>{{ $currency }}:</span>@if ($details['difference'] > 0)
                                        <span
                                            class="text-red-600"> Overpaid {{ number_format($details['difference'], 2) }}</span>
                                    @elseif($details['difference'] == 0)
                                        <span class="text-green-500"> Settled </span>
                                    @else
                                        <span
                                            class="text-green-500"> Underpaid {{ number_format(abs($details['difference']), 2) }}</span>
                                    @endif</pre>
                            @endforeach
                        </div>
                    @else
                        <pre class="text-green-600">No discrepancies found.</pre>
                    @endif
                </x-Summary.tooltip>
            </div>
            <div class="proforma-details-box bg-white p-4 rounded-lg shadow-sm cursor-help">
                <x-Summary.tooltip
                    tooltip="Discrepancies between between the supplier's requested amount in contract and the Total Payment."
                >
                    <div class="font-medium flex items-center space-x-2">
                        <span class="material-icons-outlined text-yellow-500">compare_arrows</span>
                        <span>Payment Discrepancy:</span>
                    </div>
                    @if (!empty($businessInsights->payment_status_by_currency))
                        <div class="mt-2 bold">
                            @foreach ($businessInsights->payment_status_by_currency as $currency => $details)
                                <pre><span>{{ $currency }}:</span> @if (str_contains($details['status'], 'Overpaid'))
                                        <span class="text-red-600">{{ $details['status'] }}</span>
                                    @elseif (str_contains($details['status'], 'Underpaid'))
                                        <span class="text-green-500">{{ $details['status'] }}</span>
                                    @else
                                        <span class="text-green-500">{{ $details['status'] }}</span>
                                    @endif</pre>
                            @endforeach
                        </div>
                    @else
                        <pre class="text-green-600">No discrepancies found.</pre>
                    @endif
                </x-Summary.tooltip>
            </div>
        </div>
    </div>
</div>

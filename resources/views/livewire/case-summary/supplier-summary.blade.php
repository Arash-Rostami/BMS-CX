<div x-data="{ showDetails: false }">
    {{-- Header / Currency-summary --}}
    <div class="mb-4">
        <h3 class="font-bold">
            {{ $proformasForSupplier->first()->supplier->name ?? '' }} Balance Statement:
        </h3>

        @if (empty($currencyDiffBalances))
            <p>No balance differences to display.</p>
        @else
            <div class="overflow-x-auto">
                <table class="table-auto w-full">
                    <thead>
                    <tr>
                        <th scope="col" class="border px-4 py-2 text-left">Currency</th>
                        <th scope="col" class="border px-4 py-2 text-left">Total Balance</th>
                    </tr>
                    </thead>

                    <tbody>
                    @foreach ($currencyDiffBalances as $currency => $balance)
                        @php
                            $total    = $balance['total'] ?? 0;
                            $adjusted = $balance['adjusted'] ?? 0;

                            [$balanceClass, $balanceLabel, $badgeClass] = match (true) {
                                $adjusted > 0 => ['text-danger', 'Overpaid', 'cancelled'],
                                $adjusted < 0 => ['text-success', 'Underpaid', 'approved'],
                                default       => ['', 'Settled', 'settled'],
                            };
                        @endphp

                        <tr>
                            <td class="border px-4 py-2">{{ $currency }}</td>

                            <td class="border px-4 py-2">
                                    <span class="status-badge {{ $badgeClass }} text-xl">
                                        {{ number_format(abs($adjusted), 2) }}
                                        <span class="sr-only"> — </span>
                                        {{ $balanceLabel }}
                                    </span>

                                @if ($currency !== 'Rial')
                                    <span class="text-sm insight">
                                            &nbsp;(With processing: {{ number_format(abs($total), 2) }})
                                        </span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>

    {{-- Controls --}}
    <div>
        <div class="mb-3 flex items-center gap-2">
            <button
                @click="showDetails = !showDetails"
                :aria-expanded="showDetails"
                class="details-toggle-btn"
                :class="{ 'btn-active': showDetails }"
            >
                <span class="material-icons-outlined" style="font-size: 1rem; vertical-align: middle;">
                    <span x-text="showDetails ? 'visibility_off' : 'visibility'"></span>
                </span>

                <span x-text="showDetails ? 'Hide Variance & Credit' : 'Show Variance & Credit'"></span>
            </button>

            <button
                wire:click="toggleNonFinalized"
                class="details-toggle-btn"
                :class="{ 'btn-active': @js($includeNonFinalized) }"
                title="Toggle inclusion of payments for non-finalized orders"
            >
                <span class="material-icons-outlined" style="font-size: 1rem; vertical-align: middle;">
                    {{ $includeNonFinalized ? 'check_box' : 'check_box_outline_blank' }}
                </span>

                <span>Include Payments for Non Finalized Orders</span>
            </button>
        </div>

        {{-- Main table --}}
        <div class="overflow-x-auto">
            <table class="table-auto w-full">
                <thead>
                <tr>
                    <th scope="col" class="border px-4 py-2 text-left">Contract</th>
                    <th scope="col" class="border px-4 py-2 text-left">Currency</th>
                    <th scope="col" class="border px-4 py-2 text-left">Paid</th>
                    <th scope="col" class="border px-4 py-2 text-left">Expected</th>
                    <th scope="col" class="border px-4 py-2 text-left" x-show="showDetails" x-transition>Variance</th>
                    <th scope="col" class="border px-4 py-2 text-left" x-show="showDetails" x-transition>Credit</th>
                    <th scope="col" class="border px-4 py-2 text-left">Net Balance</th>
                    <th scope="col" class="border px-4 py-2 text-left">Status</th>
                </tr>
                </thead>

                <tbody>
                @foreach ($paginatedData->sortBy(fn ($item) => $item['type'] === 'adjustment' ? 1 : 0) as $row)
                    @php
                        $isAdjustment        = $row['type'] === 'adjustment';
                        $isNotDetermined     = $row['expected_amount'] == 0;
                        $hasIncompleteRequest = !$isAdjustment && !empty($row['incompleteOrder']) && count($row['incompleteOrder']);
                        $isAdvance           = !$isAdjustment && $row['proforma']->orders->isEmpty();
                        $insightClass        = ($isAdvance || ($isNotDetermined && !$isAdjustment)) ? 'insight' : '';

                        $balance = (float) str_replace(',', '', $row['balance']);
                        $credit  = (float) str_replace(',', '', $row['credit']);
                        $variance = (float) str_replace([' ', ','], '', $row['variance']);

                        [$statusLabel, $statusClass] = match (true) {
                            $balance > 0  => ['▲ OVERPAID', 'cancelled'],
                            $balance < 0  => ['▼ UNDERPAID', 'approved'],
                            default       => ['√ SETTLED', 'settled'],
                        };
                    @endphp

                    <tr>
                        {{-- Contract / link or adjustment --}}
                        <td
                            title="{{ $isAdjustment ? 'Manual adjustment entry' : 'View contract details' }}"
                            class="border px-4 py-2 cursor-pointer"
                        >
                            @if ($isAdjustment)
                                <span class="status-badge material-icons-outlined" title="Manual adjustment entry">swap_horiz</span>
                                <span class="{{ $insightClass }}" title="Manual adjustment entry">
                                        {{ $row['contract_number'] }}
                                    </span>
                            @else
                                <a
                                    href="{{ route('filament.admin.resources.proforma-invoices.edit', ['record' => $row['id']]) }}"
                                    target="_blank"
                                    class="flex items-center space-x-2"
                                >
                                    <span class="status-badge material-icons-outlined" title="Proforma Invoice">receipt_long</span>
                                    <span class="{{ $insightClass }}">
                                            {{ $row['contract_number'] }} ({{ $row['reference_number'] }})
                                        </span>

                                    @if ($isAdvance)
                                        <span class="mt-1 insight text-sm md:text-md" title="Advance payment without associated order">⚠️ Advance Only</span>
                                    @elseif ($hasIncompleteRequest)
                                        <span class="mt-1 insight text-sm md:text-md" title="Pending payments: {{ collect($row['incompleteOrder'])->pluck('reference_number')->join(' | ') }}">⚠️ Pending Payments</span>
                                    @elseif ($isNotDetermined)
                                        <span class="mt-1 insight text-sm md:text-md" title="Orders not closed/approved yet">⚠️ Pending Finalization</span>
                                    @endif
                                </a>
                            @endif
                        </td>

                        {{-- Currency / Paid / Expected --}}
                        <td class="border px-4 py-2 {{ $insightClass }}">{{ $row['paid_currency'] }}</td>
                        <td class="border px-4 py-2 {{ $insightClass }}">{{ $row['paid_amount'] }}</td>
                        <td class="border px-4 py-2 {{ $insightClass }}">{{ $row['expected_amount'] }}</td>

                        {{-- Variance (Alpine controlled visibility) --}}
                        <td
                            class="border px-4 py-2 {{ $insightClass }}"
                            title="{{ $variance < 0 ? 'Payment Short' : ($variance > 0 ? 'Payment Excess' : '') }}"
                            x-show="showDetails"
                            x-transition
                        >
                            {{ $row['variance'] }}
                        </td>

                        {{-- Credit --}}
                        <td
                            class="border px-4 py-2 balance-credit"
                            :class="{ 'text-green-500': {{ $credit > 0 ? 'true' : 'false' }}, 'text-gray-500': {{ $credit == 0 ? 'true' : 'false' }} }"
                            x-show="showDetails"
                            x-transition
                        >
                            {{ $row['credit'] }}
                        </td>

                        {{-- Net Balance --}}
                        <td
                            class="border px-4 py-2 balance-net"
                            :class="{
                                    'text-red-600 font-semibold': {{ $balance > 0 ? 'true' : 'false' }},
                                    'text-green-500 font-semibold': {{ $balance < 0 ? 'true' : 'false' }},
                                    'text-gray-600': {{ $balance == 0 ? 'true' : 'false' }}
                                }"
                        >
                            {{ $row['balance'] }}
                        </td>

                        {{-- Status --}}
                        <td class="border px-4 py-2 {{ $insightClass }}">
                            <span class="status-badge {{ $statusClass }}">{{ $statusLabel }}</span>
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>

        {{-- Pagination --}}
        <div class="mt-4 flex items-center gap-2">
            <button
                wire:click="previousPage"
                @if ($currentPage == 1) disabled @endif
                @class(['pagination-button', 'disabled' => $currentPage == 1, 'enabled' => $currentPage != 1])
            >
                Previous
            </button>

            <span class="pagination-summary">
                <span class="total-items">{{ number_format($totalItems) }} items</span>
                &nbsp; | &nbsp;
                <span class="page-info">Page {{ $currentPage }} of {{ $totalPages }}</span>
            </span>

            <button
                wire:click="nextPage"
                @if ($currentPage >= $totalPages) disabled @endif
                @class(['pagination-button', 'disabled' => $currentPage >= $totalPages, 'enabled' => $currentPage < $totalPages])
            >
                Next
            </button>
        </div>
    </div>
</div>

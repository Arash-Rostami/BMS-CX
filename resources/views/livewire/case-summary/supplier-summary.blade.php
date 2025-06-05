<div>
    <div class="mb-4">
        <h3 class="font-bold">{{ $proformasForSupplier->first()->supplier->name ?? '' }} Balance Statement:</h3>
        @if(empty($currencyDiffBalances))
            <p>No balance differences to display.</p>
        @else
            <table class="table-auto">
                <thead>
                <tr>
                    <th class="border px-4 py-2">Currency</th>
                    <th class="border px-4 py-2">Total Balance</th>
                </tr>
                </thead>
                <tbody>
                @foreach($currencyDiffBalances as $currency => $balance)
                    @php
                        $total = $balance['total'] ?? 0;
                        $adjusted = $balance['adjusted'] ?? 0;
                        [$balanceClass, $balanceLabel, $badgeClass] =  match (true) {
                            $adjusted > 0 => ['text-danger', 'Overpaid', 'cancelled'],
                            $adjusted < 0 => ['text-success', 'Underpaid', 'approved'],
                            default      => ['', 'Settled', 'settled'],
                        };
                    @endphp
                    <tr>
                        <td class="border px-4 py-2">{{ $currency }}</td>
                        <td class="border px-4 py-2">
                            <span
                                class="status-badge {{ $badgeClass }} text-xl">{{ number_format(abs($adjusted), 2) }} {{ $balanceLabel }}</span>
                            @if($currency !== 'Rial')
                                <span class="text-sm insight"> (Net Balance: {{ number_format(abs($total), 2) }})</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        @endif
    </div>
    <div>
        <div class="overflow-x-auto">
            <table class="table-auto">
                <thead>
                <tr>
                    <th class="border px-4 py-2">Contract Number</th>
                    <th class="border px-4 py-2">Currency</th>
                    <th class="border px-4 py-2">Paid Amount</th>
                    <th class="border px-4 py-2">Expected Amount</th>
                    <th class="border px-4 py-2">Balance (Difference)</th>
                    <th class="border px-4 py-2">Credit Status</th>
                </tr>
                </thead>
                <tbody>
                @foreach ($paginatedData as $row)
                    @php
                        $isAdjustment = $row['type'] === 'adjustment';
                        $hasIncompleteRequest = !$isAdjustment && !empty($row['incompleteOrder']) && count($row['incompleteOrder']);
                        $isAdvance = !$isAdjustment && $row['proforma']->orders->isEmpty();
                        $insightClass = ($isAdvance || $isAdjustment) ? 'insight' : '';
                    @endphp

                    <tr>
                        <td
                            title="{{ $isAdjustment ? 'Adjustment Record' : 'View details of PI No: ' . $row['proforma_number'] }}"
                            class="border px-4 py-2 cursor-pointer"
                        >
                            @if ($isAdjustment)
                                <span
                                    class="status-badge material-icons-outlined"
                                    title="Manual adjustment entry"
                                >
                        swap_horiz
                    </span>
                                <span class="{{ $insightClass }}" title="Manual adjustment entry">{{ $row['contract_number'] }}
                    </span>
                            @else
                                <a
                                    href="{{ route('filament.admin.resources.proforma-invoices.edit', ['record' => $row['id']]) }}"
                                    target="_blank"
                                    class="flex items-center space-x-2"
                                >
                        <span
                            class="status-badge material-icons-outlined"
                            title="Proforma Invoice"
                        >
                            receipt_long
                        </span>
                                    <span class="{{ $insightClass }}">
                            {{ $row['contract_number'] }} ({{ $row['reference_number'] }})
                        </span>

                                    @if ($isAdvance)
                                        <span
                                            class="mt-1 text-sm insight"
                                            title="Advance payment only; no associated order has been created for this contract."
                                        >
                                ⚠️ No Order
                            </span>
                                    @elseif ($hasIncompleteRequest)
                                        <span
                                            class="mt-1 text-sm insight"
                                            title="Pending payments for payment requests: {{ collect($row['incompleteOrder'])->pluck('reference_number')->join(' | ') }}"
                                        >
                                ⚠️ No Payments
                            </span>
                                    @endif
                                </a>
                            @endif
                        </td>

                        <td class="border px-4 py-2 {{ $insightClass }}">
                            {{ $row['paid_currency'] }}
                        </td>

                        <td class="border px-4 py-2 {{ $insightClass }}">
                            {{ $row['paid_amount'] }}
                        </td>

                        <td class="border px-4 py-2 {{ $insightClass }}">
                            {{ $row['expected_amount'] }}
                        </td>

                        <td class="border px-4 py-2 {{ $insightClass }}">
                            {{ $row['diff'] }}
                        </td>

                        <td class="border px-4 py-2 {{ $insightClass }}">
                            @if ($row['diff_status'] === 'Overpaid')
                                <span class="status-badge cancelled">
                        {{ $row['diff_status'] }}
                    </span>
                            @elseif ($row['diff_status'] === 'Underpaid')
                                <span class="status-badge approved">
                        {{ $row['diff_status'] }}
                    </span>
                            @elseif ($row['diff_status'] === 'Settled')
                                <span class="status-badge settled">
                        {{ $row['diff_status'] }}
                    </span>
                            @else
                                {{ $row['diff_status'] }}
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <div style="margin-top: 1rem; display: flex; align-items: center; gap: 0.5rem;">
            <button wire:click="previousPage" @if ($currentPage == 1) disabled @endif
                @class(['pagination-button', 'disabled' => $currentPage == 1, 'enabled' => $currentPage != 1])>
                Previous
            </button>
            <span class="pagination-summary">
                <span class="total-items">{{ number_format($totalItems) }} items</span> &nbsp; | &nbsp;
                <span class="page-info">Page {{ $currentPage }} of {{ $totalPages }}</span>
            </span>
            <button wire:click="nextPage" @if ($currentPage >= $totalPages) disabled @endif
                @class(['pagination-button', 'disabled' => $currentPage >= $totalPages, 'enabled' => $currentPage < $totalPages])>
                Next
            </button>
        </div>
    </div>
</div>

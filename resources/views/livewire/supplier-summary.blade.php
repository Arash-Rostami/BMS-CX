<div>
    <div class="mb-4">
        <h3 class="font-bold">{{ $proformasForSupplier->first()->supplier->name ?? '' }}  Balance Differences:</h3>
        @if(empty($currencyDiffBalances))
            <p>No balance differences to display.</p>
        @else
            <ul class="list-none pl-5">
                @foreach($currencyDiffBalances as $currency => $balance)
                    <li>
                        @php
                            if ($balance > 0) {
                                $balanceClass = 'text-danger';
                                $balanceLabel = 'Overpaid';
                            } elseif ($balance < 0) {
                                $balanceClass = 'text-success';
                                $balanceLabel = 'Underpaid';
                            } else {
                                $balanceClass = '';
                                $balanceLabel = 'Settled';
                            }
                        @endphp
                        <span class="font-bold">{{ $currency }}:</span>
                        <span class="text-lg {{ $balanceClass }}">{{ number_format(abs($balance), 2) }} {{ $balanceLabel }}</span>
                    </li>
                @endforeach
            </ul>
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
                    <th class="border px-4 py-2">Difference</th>
                    <th class="border px-4 py-2">Credit Status</th>
                </tr>
                </thead>
                <tbody>
                @foreach($supplierPaymentSummaryTable as $row)
                    <tr>
                        <td class="border px-4 py-2 cursor-pointer" title="View details of this proforma invoice">
                            <a target="_blank"
                               href="{{ route('filament.admin.resources.proforma-invoices.edit', ['record' => $row['id']]) }}">
                                {{ $row['contract_number'] }} ({{ $row['reference_number']  }} )
                            </a>
                        </td>
                        <td class="border px-4 py-2">{{ $row['paid_currency'] }}</td> {{-- Display Currency --}}
                        <td class="border px-4 py-2">{{ $row['paid_amount'] }}</td>
                        <td class="border px-4 py-2">{{ $row['expected_amount'] }}</td>
                        <td class="border px-4 py-2">{{ $row['diff'] }}</td>
                        <td class="border px-4 py-2">
                            @if($row['diff_status'] === 'Overpaid')
                                <span class="status-badge cancelled">{{ $row['diff_status'] }}</span>
                            @elseif($row['diff_status'] === 'Underpaid')
                                <span class="status-badge approved">{{ $row['diff_status'] }}</span>
                            @elseif($row['diff_status'] === 'Settled')
                                <span class="status-badge settled">{{ $row['diff_status'] }}</span>
                            @else
                                {{ $row['diff_status'] }}
                            @endif
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

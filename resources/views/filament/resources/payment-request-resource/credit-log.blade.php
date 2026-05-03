<div>
    <div class="space-y-4">
        @if($transactions->isEmpty())
            <p class="text-sm text-gray-500">No overpaid transactions found for this supplier.</p>
        @else
            <table class="w-full text-sm text-left border rounded-lg">
                <thead class="text-xs text-gray-700 bg-gray-50">
                    <tr>
                        <th class="px-4 py-2 border-b">Contract No.</th>
                        <th class="px-4 py-2 border-b">Status</th>
                        <th class="px-4 py-2 border-b">Available Credit</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($transactions as $transaction)
                        <tr class="bg-white border-b hover:bg-gray-50">
                            <td class="px-4 py-2">{{ $transaction->contract_number ?? 'N/A' }}</td>
                            <td class="px-4 py-2">
                                <span class="px-2 py-1 text-xs font-medium text-green-800 bg-green-100 rounded-full">
                                    {{ $transaction->status }}
                                </span>
                            </td>
                            <td class="px-4 py-2 font-medium text-green-600">
                                {{ number_format($transaction->diff, 2) }} {{ $currency }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </div>
</div>

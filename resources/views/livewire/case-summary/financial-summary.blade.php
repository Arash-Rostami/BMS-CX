<div>
    <div class="flex space-x-2 mb-0 justify-end">
        <button
            title="download PDF"
            wire:click="exportPdf"
            class="px-3 py-1 bg-gray-500 text-white rounded flex items-center justify-center"
            aria-label="Download PDF"
        >
            <span class="material-icons-outlined text-base">file_download</span>
        </button>
    </div>
    <div class="mb-4">
        <h3 class="font-bold">PI and Orders Details :</h3>
    </div>
    <div>
        <div class="overflow-x-auto">
            <table class="table-auto w-full border-collapse">
                <tbody>
                <tr class="bg-gray-100">
                    <th class="border px-4 py-2 cursor-pointer">
                        <div class="font-medium pb-4">
                            <span class="material-icons-outlined text-gray-500">tag</span> Reference No.:
                        </div>
                        <x-Summary.tooltip
                            href="{{ route('filament.admin.resources.proforma-invoices.edit', ['record' => $proformaInvoice->id]) }}"
                            target="_blank"
                            tooltip="{{ $proformaInvoice->category->name }} - {{ $proformaInvoice->product->name }} ({{ $proformaInvoice->grade->name ?? 'N/A'}})"
                        >
                            <pre class="font-normal"><span class="text-xs">🔗</span> {{ $proformaInvoice->reference_number }}</pre>
                        </x-Summary.tooltip>
                    </th>
                    <td class="border px-4 py-2 help-cursor">
                        <div class="pb-4">
                            <span class="material-icons-outlined text-gray-500">file_copy</span>
                            <span class="font-medium">Proforma No.:</span>
                        </div>
                        <div class="flex items-center space-x-2">
                            <x-Summary.tooltip
                                tooltip="Contract No: {{ $proformaInvoice->contract_number ?? '' }}"
                            >
                                <span class="font-normal font-mono">{{ $proformaInvoice->proforma_number }}</span>
                            </x-Summary.tooltip>
                            @php
                                $attachment = $proformaInvoice->attachments->first(fn($a) => trim($a->name) == 'pi') ??
                                $proformaInvoice->attachments->first(fn($a) => trim($a->name) == 'original-pi');
                            @endphp
                            @if($attachment)
                                <x-Summary.tooltip
                                    href="{{ $attachment['file_path'] }}"
                                    target="_blank"
                                    tooltip="View {{ $attachment['name'] }}"
                                >
                                    <span class="material-icons-outlined text-xs main-color-complement">attach_file</span>
                                </x-Summary.tooltip>
                            @endif
                        </div>
                    </td>
                    <td class="border px-4 py-2">
                        <div class="font-medium pb-4">
                            <span class="material-icons-outlined text-gray-500">event</span> Proforma Date:
                        </div>
                        <pre class="font-normal">    {{ $proformaInvoice->proforma_date->format('d M, Y') }}</pre>
                    </td>
                    <td class="border px-4 py-2">
                        <div class="font-medium pb-4">
                            <span class="material-icons-outlined text-gray-500">person</span> Buyer:
                        </div>
                        <pre class="font-normal">  {{ $proformaInvoice->buyer->name }}</pre>
                    </td>
                    <td class="border px-4 py-2" colspan="2">
                        <div class="font-medium pb-4">
                            <span class="material-icons-outlined text-gray-500">business</span> Supplier:
                        </div>
                        <pre class="font-normal">  {{ $proformaInvoice->supplier->name }}</pre>
                    </td>
                </tr>
                <tr class="bg-gray-100 w-full">
                    <td class="border px-4 py-2">
                        <div class="font-medium pb-4">
                            <span class="material-icons-outlined text-gray-500">percent</span> Advance
                            ({{ $proformaInvoice->percentage ?? 0 }}):
                        </div>
                        <pre
                            class="font-normal">{{ number_format(($proformaInvoice->percentage * ($proformaInvoice->quantity * $proformaInvoice->price))/100 ?? 0, 2) }}</pre>
                    </td>
                    <td class="border px-4 py-2">
                        <div class="font-medium pb-4">
                            <span class="material-icons-outlined text-gray-500">view_module</span> Quantity:
                        </div>
                        <pre class="font-normal"> {{ number_format($proformaInvoice->quantity ?? 0, 0) }} mt</pre>
                    </td>
                    <td class="border px-4 py-2">
                        <div class="font-medium pb-4">
                            <span class="material-icons-outlined text-gray-500">attach_money</span> Price:
                        </div>
                        <pre class="font-normal"> {{ number_format($proformaInvoice->price ?? 0, 2) }}</pre>
                    </td>
                    <td class="border px-4 py-2" colspan="3">
                        <div class="font-medium pb-4 flex items-center space-x-2">
                            <span class="material-icons-outlined text-gray-500">credit_card</span>
                            <span>Total:</span>
                        </div>
                        <pre
                            class="font-normal"> {{ number_format(($proformaInvoice->quantity * $proformaInvoice->price) ?? 0, 2) }}</pre>
                    </td>
                </tr>
                <tr class="bg-gray-100">
                    <th class="border px-4 py-2">Part</th>
                    <th class="border px-4 py-2">BL Date</th>
                    <th class="border px-4 py-2">Prices (P | F)</th>
                    <th class="border px-4 py-2">Quantity</th>
                    <th class="border px-4 py-2">Advance</th>
                    <th class="border px-4 py-2">Total</th>
                </tr>
                @foreach($orderSummary['rows'] as $row)
                    <tr>
                        <td class="border px-4 py-2 help-cursor">
                            <div class="flex items-center space-x-2">
                                <x-Summary.tooltip
                                    href="{{ route('filament.admin.resources.orders.edit', ['record' => $row['id']]) }}"
                                    target="_blank"
                                    tooltip="{{$row['status']}}: {{$row['stage']}} stage"
                                >
                                    <span class="text-xs">🔗 </span><span class="font-normal font-mono">{{ $row['part'] }} ({{ $row['reference_number'] }})</span>
                                </x-Summary.tooltip>
                                @foreach($row['doc_attachments'] as $doc)
                                    <x-Summary.tooltip
                                        href="{{ $doc['url'] }}"
                                        target="_blank"
                                        tooltip="View {{ $doc['name'] }}"
                                    >
                                        <span class="material-icons-outlined text-xs font-mono main-color-complement">attach_file</span>
                                        @unless ($loop->last)
                                            <span>⋮</span>
                                        @endunless
                                    </x-Summary.tooltip>
                                @endforeach
                            </div>
                        </td>
                        <td class="border px-4 py-2 help-cursor">
                            <div class="flex items-center space-x-2">
                                <x-Summary.tooltip
                                    tooltip="Destination: {{ $row['port_of_delivery'] }}"
                                >
                                    <pre class="font-normal">{{ $row['bl_date'] ?? 'N/A' }}</pre>
                                </x-Summary.tooltip>
                                @if($row['bl_attachment'])
                                    <x-Summary.tooltip
                                        href="{{ $row['bl_attachment']['url'] }}"
                                        target="_blank"
                                        tooltip="View {{ $row['bl_attachment']['name'] }}"
                                    >
                                        <span class="material-icons-outlined text-xs font-mono main-color-complement">attach_file</span>
                                    </x-Summary.tooltip>
                                @endif
                            </div>
                        </td>
                        <td class="border px-4 py-2">
                            <pre
                                class="font-normal"> {{ $row['currency'] ?? '' }} {{ is_numeric($row['provisional_price']) ? number_format($row['provisional_price'], 2) : ($row['provisional_price'] ?? 'N/A') }} | {{ is_numeric($row['final_price']) ? number_format($row['final_price'], 2) : ($row['final_price'] ?? 'N/A') }}</pre>
                        </td>
                        <td class="border px-4 py-2">
                            <pre
                                class="font-normal">{{ is_numeric($row['quantity']) ? number_format($row['quantity'], 2) : 'N/A' }}</pre>
                        </td>
                        <td class="border px-4 py-2 help-cursor">
                            <x-Summary.tooltip
                                tooltip="+ {{ number_format($row['payment'],2) }}"
                            >
                                <pre
                                    class="font-normal">{{ is_numeric($row['initial_payment']) ? number_format($row['initial_payment'], 2) : 'N/A' }}</pre>
                            </x-Summary.tooltip>
                        </td>
                        <td class="border px-4 py-2">
                            <pre
                                class="font-normal">{{ is_numeric($row['total']) ? number_format($row['total'], 2) : 'N/A' }}</pre>
                        </td>
                    </tr>
                @endforeach
                {{-- Sum row --}}
                <tr class="font-semibold bg-gray-50">
                    <th colspan="3" class="border px-4 py-2">Sum</th>
                    <td class="border px-4 py-2">
                        <pre class="font-normal">{{ number_format($orderSummary['totals']['quantity'], 2) }}</pre>
                    </td>
                    <td class="border px-4 py-2">
                        <pre title="+ {{ number_format($orderSummary['totals']['payment'], 2)  }}"
                             class="font-normal">{{ number_format($orderSummary['totals']['initial_payment'], 2) }}</pre>
                    </td>
                    <td class="border px-4 py-2">
                        <pre class="font-normal">{{ number_format($orderSummary['totals']['sum'], 2) }}</pre>
                    </td>
                </tr>
                </tbody>
            </table>
        </div>
        <div class="mb-4">
            <h3 class="font-bold"> Payment Details :</h3>
        </div>
        <div>
            <div class="overflow-x-auto">
                <table class="table-auto">
                    <tbody>
                    {{-- Group headers --}}
                    <tr class="bg-gray-100">
                        <th class="border px-4 py-2">Reference No</th>
                        <th class="border px-4 py-2">SWIFT</th>
                        <th class="border px-4 py-2">Receipt(s)</th>
                        <th class="border px-4 py-2">Payer</th>
                        <th class="border px-4 py-2">Deadline</th>
                        <th class="border px-4 py-2">Value Date</th>
                        <th class="border px-4 py-2">Amount</th>
                        <th class="border px-4 py-2">Balance</th>
                    </tr>
                    @foreach($paymentSummary['rows'] as $row)
                        <tr>
                            <td class="border px-4 py-2 cursor-pointer">
                                <x-Summary.tooltip
                                    href="{{ route('filament.admin.resources.payments.edit', ['record' => $row['id']]) }}"
                                    target="_blank"
                                    tooltip="{{ $row['type'] ?? 'N/A' }} for {{ $row['order_reference_number'] ?? $proformaInvoice->reference_number}} by {{ $row['request_reference_number']  ?? 'N/A' }}"
                                >
                                    <pre class="font-normal"><span
                                            class="text-xs">🔗</span> {{ $row['reference_number'] }} {{ $row['advance'] ? '⭐': '🛒'}}</pre>
                                </x-Summary.tooltip>
                            </td>
                            <td class="border px-4 py-2 help-cursor">
                                <x-Summary.tooltip
                                    href="{{ route('filament.admin.resources.payment-requests.edit', ['record' => $row['request_id']]) }}"
                                    target="_blank"
                                    tooltip="{{ $row['account']}}"
                                >
                                    <pre class="font-normal"><span class="text-xs">🔗</span> {{ $row['swift'] }}</pre>
                                </x-Summary.tooltip>
                            </td>
                            <td class="border px-4 py-2 text-center">
                                @if(!empty($row['receipts']))
                                    <div class="flex flex-row space-x-1 justify-center">
                                        @foreach($row['receipts'] as $receipt)
                                            <x-Summary.tooltip
                                                href="{{ $receipt['url'] }}"
                                                target="_blank"
                                                tooltip="View {{ $receipt['name'] }}"
                                            >
                                                <span class="material-icons-outlined text-xs main-color-complement">attach_file</span>
                                                @unless ($loop->last)
                                                    <span>⋮</span>
                                                @endunless
                                            </x-Summary.tooltip>
                                        @endforeach
                                    </div>
                                @else
                                    <span class="text-gray-300 text-sm cursor-not-allowed"
                                          title="Not attached!">🚫</span>
                                @endif
                            </td>
                            <td class="border px-4 py-2 help-cursor">
                                <x-Summary.tooltip
                                    tooltip="Recipient: {{ $row['recipient'] }}"
                                >
                                    <pre class="font-normal"> {{ $row['payer'] }}</pre>
                                </x-Summary.tooltip>
                            </td>
                            <td class="border px-4 py-2 help-cursor">
                                <x-Summary.tooltip
                                    tooltip="Requested by {{ $row['request_created_by']}} on {{ $row['request_created_at'] }}"
                                >
                                    <pre class="font-normal"> {{ $row['deadline'] }}</pre>
                                </x-Summary.tooltip>
                            </td>
                            <td class="border px-4 py-2 help-cursor">
                                <x-Summary.tooltip
                                    tooltip="{{ is_numeric($row['diff']) ? ($row['diff'] < 0 ? abs($row['diff']) . ' days later' : ($row['diff'] > 0 ? abs($row['diff']) . ' days sooner' : 'On time')) : '' }}"
                                >
                                    <pre
                                        class="font-normal {{ is_numeric($row['diff']) ? ($row['diff'] < 0 ? 'text-danger' : '') : '' }}"> {{ $row['value_date'] }}</pre>
                                </x-Summary.tooltip>
                            </td>
                            <td class="border px-4 py-2 help-cursor">
                                <x-Summary.tooltip
                                    tooltip="Created by {{ $row['created_by'] }} on {{ $row['created_at'] }}"
                                >
                                    <pre class="font-normal"> {{ number_format($row['amount'],2) }}</pre>
                                </x-Summary.tooltip>
                            </td>
                            <td class="border px-4 py-2 help-cursor">
                                <x-Summary.tooltip
                                    tooltip="{{ $row['currency'] }}"
                                >
                                    <pre class="font-normal"> {{ number_format($row['balance'],2) }}</pre>
                                </x-Summary.tooltip>
                            </td>
                        </tr>
                    @endforeach
                    <tr class="font-semibold bg-gray-50">
                        <th colspan="6" class="border px-4 py-2">
                            Sum
                        </th>
                        <th class="border px-4 py-2">
                            <pre class="font-normal">{{ number_format($paymentSummary['totals']['paid'],2) }}</pre>
                        </th>
                        <th class="border px-4 py-2">
                            <pre class="font-normal">{{ number_format($paymentSummary['totals']['balance'],2) }}</pre>
                        </th>
                    </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

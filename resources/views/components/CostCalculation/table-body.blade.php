<div class="overflow-x-auto">
    <table class="table-auto">
        <thead>
        <tr class="border px-4 py-2 mx-auto justify-center text-center">
            @foreach([
                'date'        => 'Date',
                'product_id'     => 'Product',
                'supplier_id'    => 'Supplier',
                'tender_no'   => 'Tender No',
                'quantity'    => 'Quantity',
                'win_price_usd' => 'Win Price',
                'total_cost'  => 'Total Cost',
                'status'      => 'Status'
            ] as $field => $label)
                <th scope="col"
                    class="sticky top-0 px-6 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300"
                >
                    <div class="flex items-center cursor-pointer justify-center"
                         title="Click to sort column in reverse order"
                         @if(in_array($field, ['date','tender_no','quantity','win_price_usd','total_cost','status', 'product_id', 'supplier_id']))
                             wire:click="sortBy('{{ $field }}')"
                        @endif
                    >
                        {{ $label }}
                        @if($sortField === $field)
                            <span class="material-icons-outlined text-sm ml-1">
                                        {{ $sortDirection === 'asc' ? 'arrow_upward' : 'arrow_downward' }}
                            </span>
                        @endif
                    </div>
                </th>
            @endforeach
            <th scope="col"
                class="sticky top-0 px-6 py-3 text-center text-xs font-medium uppercase tracking-wider text-gray-500 dark:text-gray-300">
                <div class="flex justify-center">
                    Actions
                </div>
            </th>
        </tr>
        </thead>
        <tbody class="">
        @forelse($costCalculations as $item)
            <tr class="">
                <td class="border px-4 py-2 cursor-help"
                    title="Validity:  {{ $item->validity ? $item->validity->format('d, m, Y') : '-' }}">
                    {{ $item->date ? $item->date->format('d, m, Y') : '-' }}
                </td>
                <td class="border px-4 py-2 cursor-help" title="{{ optional($item->grade)->name ?? ''}}">
                    {{ $item->product->name ?? '-' }}
                </td>
                <td class="border px-4 py-2 cursor-help" title="{{ $item->transport_type ?? ''}}">
                    {{ $item->supplier->name ?? '-' }}
                </td>
                <td class="border px-4 py-2 cursor-help" title="{{ $item->term ?? ''}}">
                    {{ $item->tender_no ?? '-' }}
                </td>
                <td class="border px-4 py-2 cursor-help" title="{{ optional($item->packaging)->name ?? ''}}">
                    {{ $item->quantity ? number_format($item->quantity, 2) . ' MT' : '-' }}
                </td>
                <td class="border px-4 py-2 cursor-help"
                    title="{{ $item->persol_price_usd ? 'Persol Price: $' . number_format($item->persol_price_usd, 2) : '' }}">
                    {{ $item->win_price_usd ? '$' . number_format($item->win_price_usd, 2) : '-' }}
                </td>
                <td class="border px-4 py-2 cursor-help"
                    title="Price Diff: {{ $item->price_difference ? '$' . number_format($item->price_difference, 2) : '-' }}">
                    {{ $item->total_cost ? '$' . number_format($item->total_cost, 2) : '-' }}
                </td>
                <td class="border px-4 py-2 whitespace-nowrap">
                    @if($item->status)
                        <span class="badge
                                @switch($item->status)
                                    @case('Accepted') badge-accepted @break
                                    @case('Rejected') badge-rejected @break
                                    @case('Sold')     badge-sold @break
                                    @default          badge-default
                                @endswitch
                            ">
                                {{ $item->status }}
                            </span>
                    @else
                        -
                    @endif
                </td>
                <td class="px-6 py-4 whitespace-nowrap flex text-sm font-medium justify-center">
                    <button class="btn-view" wire:click="showDetails({{ $item->id }})" title="View">
                        <span class="material-icons-outlined">visibility</span>
                    </button>

                    <button
                        class="btn-edit mr-2"
                        @click="$dispatch('open-edit-calculation', {id: {{ $item->id }}})"
                        title="Edit"
                    >
                        <span class="material-icons-outlined">edit</span>
                    </button>

                    <button
                        class="btn-delete"
                        @click="$dispatch('open-delete-confirmation', { id: {{ $item->id }} })"
                        title="Delete"
                    >
                        <span class="material-icons-outlined">delete</span>
                    </button>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="9"
                    class="px-6 py-4 whitespace-nowrap text-sm text-gray-500 dark:text-gray-400 text-center">
                    No records found. Try adjusting your filters or search terms.
                </td>
            </tr>
        @endforelse
        </tbody>
    </table>
</div>

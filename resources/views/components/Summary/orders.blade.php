@if ($selectedProforma->orders->isNotEmpty())
        <?php
        $totalPayableQuantity = $selectedProforma->orders->filter(fn($order) => $order->orderDetail !== null)->sum(fn($order) => $order->orderDetail->payable_quantity);
        $proformaQuantity = $selectedProforma->quantity;

        $badgeText = '';
        $badgeColor = '';
        $difference = $totalPayableQuantity - $proformaQuantity;
        if ($totalPayableQuantity > $proformaQuantity) {
            $badgeText = 'Higher';
            $badgeColor = 'bg-red-500 text-white';
        } elseif ($totalPayableQuantity < $proformaQuantity) {
            $badgeText = 'Lower';
            $badgeColor = 'bg-green-500 text-white';
        } else {
            $badgeText = 'Equal';
            $badgeColor = 'bg-gray-500 text-white';
        }
        ?>

        <!-- Header -->
    <h3 class="mb-3">
        <span class="material-icons-outlined order-icon text-sm insight">list_alt</span>
        <span class="text-2xl font-semibold mb-4">Order(s)</span>
    </h3>

    @foreach ($selectedProforma->orders as $order)
        <!-- Accordion Container -->
        <div x-data="{ open: false }" class="border rounded-lg shadow-lg mb-2">
            <button @click="open = !open"
                    class="w-full flex justify-between items-center my-dark-class px-4 py-3 text-left text-lg font-semibold rounded-xl">
                <span> No: {{ $order->reference_number ?? 'N/A' }}</span>
                <span class="material-icons-outlined" x-show="open">expand_less</span>
                <span class="material-icons-outlined" x-show="!open">expand_more</span>
            </button>

            <!-- Accordion Content -->
            <div x-show="open"
                 x-collapse
                 class="p-4 border-t">
                <div class="proforma-details-grid">
                    <div class="proforma-details-box">
                        <div class="font-medium">
                            <span class="material-icons-outlined">tag</span> Reference No.:
                        </div>
                        <pre>{{ $order->reference_number ?? 'N/A' }}</pre>
                    </div>
                    <div class="proforma-details-box">
                        <div class="font-medium"><span class="material-icons-outlined">widgets</span> Part:</div>
                        <pre>{{ $order->part ?? 'N/A' }}</pre>
                    </div>
                    <div class="proforma-details-box">
                        <div class="font-medium"><span class="material-icons-outlined">info</span> Purchase Status:
                        </div>
                        <pre>{{ $order->purchaseStatus?->name ?? 'N/A' }}</pre>
                    </div>
                    <div class="proforma-details-box">
                        <div class="font-medium"><span class="material-icons-outlined">person</span> Created by:</div>
                        <pre>{{ $order->user?->first_name ?? 'N/A' }}</pre>
                    </div>
                    <div class="proforma-details-box">
                        <div class="font-medium"><span class="material-icons-outlined">access_time</span> Created at:
                        </div>
                        <pre>{{ $order->created_at?->format('Y-m-d') ?? 'N/A' }}</pre>
                    </div>
                    <div class="proforma-details-box">
                        <div class="font-medium"><span class="material-icons-outlined">timer</span> Last Update:</div>
                        <pre>{{ $order->updated_at?->format('Y-m-d') ?? 'N/A' }}</pre>
                    </div>
                    <div class="proforma-details-box">
                        <div class="font-medium"><span class="material-icons-outlined">archive</span> Unique ID:</div>
                        <pre>{{ $order->order_number }}</pre>
                    </div>
                    @if ($order->orderDetail)
                        <h5 class="text-xl font-semibold col-span-full mt-6 divider-attachment">Financials</h5>
                        <div class="proforma-details-box">
                            <div class="font-medium"><span class="material-icons-outlined">open_with</span> Provisional
                                Quantity:
                            </div>
                            <pre>{{ $order->orderDetail->provisional_quantity ?? 'N/A' }}</pre>
                        </div>
                        <div class="proforma-details-box">
                            <div class="font-medium"><span class="material-icons-outlined">archive</span> Final
                                Quantity:
                            </div>
                            <pre>{{ $order->orderDetail->final_quantity ?? 'N/A' }}</pre>
                        </div>
                        <div class="proforma-details-box">
                            <div class="font-medium"><span class="material-icons-outlined">attach_money</span>
                                Provisional Price:
                            </div>
                            <pre>${{ number_format($order->orderDetail->provisional_price, 2) ?? 'N/A' }}</pre>
                        </div>
                        <div class="proforma-details-box">
                            <div class="font-medium"><span class="material-icons-outlined">attach_money</span> Final
                                Price:
                            </div>
                            <pre>${{ number_format($order->orderDetail->final_price, 2) ?? 'N/A' }}</pre>
                        </div>
                        <div class="proforma-details-box">
                            <div class="font-medium"><span class="material-icons-outlined">money</span> Currency:</div>
                            <pre>{{ $order->orderDetail->currency ?? 'N/A' }}</pre>
                        </div>
                        <div class="proforma-details-box">
                            <div class="font-medium"><span class="material-icons-outlined">monetization_on</span>
                                Pre-payment:
                            </div>
                            <pre>${{ number_format($order->orderDetail->initial_total, 2) ?? 'N/A' }}</pre>
                        </div>
                        <div class="proforma-details-box">
                            <div class="font-medium"><span class="material-icons-outlined">payments</span>
                                Init. Advance:
                            </div>
                            <pre>${{ number_format($order->orderDetail->initial_payment, 2) ?? 'N/A' }}</pre>
                        </div>
                        <div class="proforma-details-box">
                            <div class="font-medium"><span class="material-icons-outlined">show_chart</span> Prov. Total:
                            </div>
                            <pre>${{ number_format($order->orderDetail->provisional_total, 2) ?? 'N/A' }}</pre>
                        </div>
                        <div class="proforma-details-box">
                            <div class="font-medium"><span class="material-icons-outlined">check_circle</span> Fin.
                                Total:
                            </div>
                            <pre>${{ number_format($order->orderDetail->final_total, 2) ?? 'N/A' }}</pre>
                        </div>
                        <div class="proforma-details-box">
                            <div class="font-medium"><span class="material-icons-outlined">calculate</span> Aggreg.
                                Total:
                            </div>
                            <pre>${{ number_format(($order->orderDetail->initial_payment ?? 0)+($order->orderDetail->provisional_total ?? 0)+($order->orderDetail->final_total ?? 0), 2) ?? 'N/A' }}</pre>
                        </div>
                        <div class="proforma-details-box">
                            <div class="font-medium"><span class="material-icons-outlined">archive</span> Payable
                                Quantity:
                            </div>
                            <pre>{{ $order->orderDetail->payable_quantity ?? 'N/A' }} <span
                                    class="px-2 py-1 rounded text-xs inline-block {{ $badgeColor }}"> {{ $badgeText }} ({{ $difference > 0 ? '+' : '' }}{{ $difference }})</span></pre>
                        </div>
                    @endif
                    @if ($order->logistic)
                        <h5 class="text-xl font-semibold col-span-full mt-6 divider-attachment">Logistics</h5>
                        <div class="proforma-details-box">
                            <div class="font-medium"><span class="material-icons-outlined">calendar_today</span> Loading
                                Deadline:
                            </div>
                            <pre>{{ $order->logistic->loading_deadline?->format('Y-m-d') ?? 'N/A' }}</pre>
                        </div>
                        <div class="proforma-details-box">
                            <div class="font-medium"><span class="material-icons-outlined">swap_horiz</span> Change of
                                Destination:
                            </div>
                            <pre>{{ $order->logistic->change_of_destination ? 'Yes' : 'No' }}</pre>
                        </div>
                        <div class="proforma-details-box">
                            <div class="font-medium"><span class="material-icons-outlined">archive</span> Containers:
                            </div>
                            <pre>{{ $order->logistic->number_of_containers }}</pre>
                        </div>
                        <div class="proforma-details-box">
                            <div class="font-medium"><span class="material-icons-outlined">local_shipping</span> Full
                                Container Load Type:
                            </div>
                            <pre>{{ $order->logistic->full_container_load_type ?? 'N/A' }}</pre>
                        </div>
                        <div class="proforma-details-box">
                            <div class="font-medium"><span class="material-icons-outlined">attach_money</span> Ocean
                                Freight:
                            </div>
                            <pre>${{ number_format($order->logistic->ocean_freight, 2) ?? 'N/A' }}</pre>
                        </div>
                        <div class="proforma-details-box">
                            <div class="font-medium"><span class="material-icons-outlined">assignment</span> Terminal
                                Handling Charges:
                            </div>
                            <pre>${{ number_format($order->logistic->terminal_handling_charges, 2) ?? 'N/A' }}</pre>
                        </div>
                        <div class="proforma-details-box">
                            <div class="font-medium"><span class="material-icons-outlined">widgets</span> FCL:</div>
                            <pre>{{ $order->logistic->FCL ?? 'N/A' }}</pre>
                        </div>
                        <div class="proforma-details-box">
                            <div class="font-medium"><span class="material-icons-outlined">directions_boat</span>
                                Booking No.:
                            </div>
                            <pre>{{ $order->logistic->booking_number ?? 'N/A' }}</pre>
                        </div>
                        <div class="proforma-details-box">
                            <div class="font-medium"><span class="material-icons-outlined">access_time</span> Free Time
                                POD:
                            </div>
                            <pre>{{ $order->logistic->free_time_POD ?? 'N/A' }}</pre>
                        </div>
                        <div class="proforma-details-box">
                            <div class="font-medium"><span class="material-icons-outlined">scale</span> Gross Weight:
                            </div>
                            <pre>{{ $order->logistic->gross_weight }} KG</pre>
                        </div>
                        <div class="proforma-details-box">
                            <div class="font-medium"><span class="material-icons-outlined">equalizer</span> Net Weight:
                            </div>
                            <pre>{{ $order->logistic->net_weight }} KG</pre>
                        </div>
                    @endif
                    @if ($order->doc)
                        <div class="proforma-details-box">
                            <div class="font-medium"><span class="material-icons-outlined">directions_boat</span> Voyage
                                No.:
                            </div>
                            <pre>{{ $order->doc->voyage_number ?? 'N/A' }}</pre>
                        </div>
                        <div class="proforma-details-box">
                            <div class="font-medium"><span class="material-icons-outlined">receipt_long</span>
                                Declaration No.:
                            </div>
                            <pre>{{ $order->doc->declaration_number ?? 'N/A' }}</pre>
                        </div>
                        <div class="proforma-details-box">
                            <div class="font-medium"><span class="material-icons-outlined">event_available</span>
                                Declaration Date:
                            </div>
                            <pre>{{ $order->doc->declaration_date?->format('Y-m-d') ?? 'N/A' }}</pre>
                        </div>
                        <div class="proforma-details-box">
                            <div class="font-medium"><span class="material-icons-outlined">insert_drive_file</span> BL
                                No.:
                            </div>
                            <pre>{{ $order->doc->BL_number ?? 'N/A' }}</pre>
                        </div>
                        <div class="proforma-details-box">
                            <div class="font-medium"><span class="material-icons-outlined">event_note</span> BL Date:
                            </div>
                            <pre>{{ $order->doc->BL_date?->format('Y-m-d') ?? 'N/A' }}</pre>
                        </div>
                    @endif
                    <h5 class="text-xl font-semibold col-span-full mt-6 divider-attachment">Attachments:</h5>
                    @foreach ($orderAttachmentNames  as $attachmentName)
                        @php
                            $attachment = $selectedProforma->orders->flatMap->attachments
                                ->firstWhere('name', $attachmentName);
                        @endphp
                        <div class="flex items-center justify-between p-2 rounded border
                {{ $attachment ? 'status-badge approved' : 'status-badge cancelled' }}">
                            <div class="flex items-center gap-2">
                                <span class="material-icons-outlined">attachment</span>
                                @if ($attachment && $attachment->file_path)
                                    <a href="{{ asset($attachment->file_path) }}" target="_blank" class="underline">
                                        <span class="text-lg font-medium">{{ $attachment->name }}</span>
                                    </a>
                                @else
                                    <span class="text-lg font-medium">{{ $attachmentName }}</span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                    @if ($orderAttachmentNames->isEmpty())
                        <div class="p-2 rounded border bg-gray-100 border-gray-400 text-gray-600">
                            No attachments found!
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endforeach
@endif

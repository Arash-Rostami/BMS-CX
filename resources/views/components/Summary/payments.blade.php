@if ($selectedProforma->associatedPaymentRequests->isNotEmpty() || $selectedProforma->orders->flatMap->paymentRequests )
    <!-- Header -->
    <h3 class="mb-3">
        <span class="material-icons-outlined payment-icon text-sm insight">payments</span>
        <span class="text-2xl font-semibold mb-4">Payment(s)</span>
    </h3>

    @foreach($selectedProforma->associatedPaymentRequests->concat($selectedProforma->orders->flatMap->paymentRequests) as $paymentRequest)
        <!-- Accordion Container -->
        <div x-data="{ open: false }" class="border rounded-lg shadow-lg mb-2">
            @php
                $paymentPart = !isset($paymentRequest->order_id) ? '' : $paymentRequest?->order->part;
                $paymentType = !isset($paymentRequest->order_id) ? ' (⭐)' : ' (🛒' .($paymentPart ?? '') .')';
                $paymentTitle = !isset($paymentRequest->order_id) ? 'Related to Proforma Invoice' : 'Related to Orders: Part ' .($paymentPart ?? '');
            @endphp
            <button @click="open = !open"
                    class="w-full flex justify-between items-center my-dark-class px-4 py-3 text-left text-lg font-semibold rounded-xl">
                <span
                    title="{{ $paymentTitle }}"> No: {{ $paymentRequest->reference_number ? $paymentRequest->reference_number .$paymentType : 'N/A' }}</span>
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
                        <pre>{{ $paymentRequest->reference_number ?? 'N/A' }}</pre>
                    </div>
                    <div class="proforma-details-box cursor-help"
                         title="{{ $paymentRequest->order_id ?
                    ('BK No: ' . ($paymentRequest->order?->logistic->booking_number ?? 'Unavailable') . ' | BL No: ' . ($paymentRequest->order?->doc->BL_number ?? 'Unavailable') )
                : ($paymentRequest->order?->proformaInvoice->proforma_number ?? 'No Booking Number') }}"
                    >
                        <div class="font-medium">
                            <span
                                class="material-icons-outlined">{{ $paymentRequest->order ? 'shopping_cart' : 'insert_drive_file' }}</span>
                            {{ $paymentRequest->order ? 'Order' : 'Proforma Invoice' }}:
                        </div>
                        <pre>{{ $paymentRequest->order ? 'Part: ' .$paymentRequest->order->part .' (' .$paymentRequest->order->reference_number .')' : $paymentRequest->associatedProformaInvoices->pluck('reference_number')->implode(' | ') }}</pre>
                    </div>
                    <div class="proforma-details-box">
                        <div class="font-medium"><span class="material-icons-outlined">description</span> Reason:</div>
                        <div class="flex items-center">
                            <pre>{{ $paymentRequest->reason?->reason ?? 'N/A' }}</pre>
                        </div>
                    </div>
                    <div class="proforma-details-box">
                        <div class="font-medium"><span class="material-icons-outlined">credit_card</span> Type:</div>
                        <div class="flex items-center">
                            <pre>{{ \App\Models\PaymentRequest::$typesOfPayment[$paymentRequest->type_of_payment] ?? 'N/A' }}</pre>
                        </div>
                    </div>
                    @if(isset($paymentRequest->purpose ))
                        <div class="proforma-details-box">
                            <div class="font-medium"><span class="material-icons-outlined">description</span> Purpose:
                            </div>
                            <div class="flex items-center">
                                <pre>{{ $paymentRequest->purpose }}</pre>
                            </div>
                        </div>
                    @endif
                    <div class="proforma-details-box">
                        <div class="font-medium"><span class="material-icons-outlined">people</span>Department:</div>
                        <div class="flex items-center">
                            <pre>{{ ucwords($paymentRequest->department?->code) ?? 'N/A' }}</pre>
                        </div>
                    </div>
                    <div class="proforma-details-box">
                        <div class="font-medium"><span class="material-icons-outlined">attach_money</span> Currency:
                        </div>
                        <div class="flex items-center">
                            <pre>{{ $paymentRequest->currency ?? 'N/A' }}</pre>
                        </div>
                    </div>
                    <div class="proforma-details-box">
                        <div class="font-medium"><span class="material-icons-outlined">monetization_on</span> Requested
                            Amount:
                        </div>
                        <div class="flex items-center">
                            <pre>{{ number_format($paymentRequest->requested_amount, 2) }}</pre>
                        </div>
                    </div>
                    <div class="proforma-details-box">
                        <div class="font-medium"><span class="material-icons-outlined">credit_card</span> Total Amount:
                        </div>
                        <div class="flex items-center">
                            <pre>{{ number_format($paymentRequest->total_amount, 2) }}</pre>
                        </div>
                    </div>
                    <div class="proforma-details-box">
                        <div class="font-medium"><span class="material-icons-outlined">calendar_today</span> Deadline:
                        </div>
                        <div class="flex items-center">
                            <pre>{{ $paymentRequest->deadline ? $paymentRequest->deadline->format('Y-m-d') : 'N/A' }}</pre>
                        </div>
                    </div>
                    <div class="proforma-details-box">
                        <div class="font-medium"><span class="material-icons-outlined">business</span> Beneficiary:
                        </div>
                        <div class="flex items-center">
                            <pre>{{ $paymentRequest->beneficiary_name == 'supplier' ? $paymentRequest->supplier?->name ?? 'N/A' : ($paymentRequest->beneficiary_name == 'contractor' ? $paymentRequest->contractor?->name ?? 'N/A' : $paymentRequest->beneficiary_name ?? 'N/A') }}</pre>
                        </div>
                    </div>
                    <div class="proforma-details-box">
                        <div class="font-medium"><span class="material-icons-outlined">person</span> Recipient Name:
                        </div>
                        <div class="flex items-center">
                            <pre>{{ $paymentRequest->recipient_name ?? 'N/A' }}</pre>
                        </div>
                    </div>
                    <div class="proforma-details-box">
                        <div class="font-medium"><span class="material-icons-outlined">location_on</span> Recipient
                            Address:
                        </div>
                        <div class="flex items-center">
                            <pre>{{ $paymentRequest->beneficiary_address ?? 'N/A' }}</pre>
                        </div>
                    </div>
                    <div class="proforma-details-box">
                        <div class="font-medium"><span class="material-icons-outlined">store</span> Bank Name:</div>
                        <div class="flex items-center">
                            <pre>{{ $paymentRequest->bank_name ?? 'N/A' }}</pre>
                        </div>
                    </div>
                    <div class="proforma-details-box">
                        <div class="font-medium"><span class="material-icons-outlined">location_on</span> Bank Address:
                        </div>
                        <div class="flex items-center">
                            <pre>{{ $paymentRequest->bank_address ?? 'N/A' }}</pre>
                        </div>
                    </div>
                    <div class="proforma-details-box">
                        <div class="font-medium"><span class="material-icons-outlined">account_balance</span> Account
                            No.:
                        </div>
                        <div class="flex items-center">
                            <pre>{{ $paymentRequest->account_number ?? 'N/A' }}</pre>
                        </div>
                    </div>
                    <div class="proforma-details-box">
                        <div class="font-medium"><span class="material-icons-outlined">code</span> SWIFT:</div>
                        <div class="flex items-center">
                            <pre>{{ $paymentRequest->swift_code ?? 'N/A' }}</pre>
                        </div>
                    </div>
                    <div class="proforma-details-box">
                        <div class="font-medium"><span class="material-icons-outlined">card_membership</span> IBAN:
                        </div>
                        <div class="flex items-center">
                            <pre>{{ $paymentRequest->IBAN ?? 'N/A' }}</pre>
                        </div>
                    </div>
                    <div class="proforma-details-box">
                        <div class="font-medium"><span class="material-icons-outlined">qr_code</span> IFSC:</div>
                        <div class="flex items-center">
                            <pre>{{ $paymentRequest->IFSC ?? 'N/A' }}</pre>
                        </div>
                    </div>
                    <div class="proforma-details-box">
                        <div class="font-medium"><span class="material-icons-outlined">filter_1</span> MICR:</div>
                        <div class="flex items-center">
                            <pre>{{ $paymentRequest->MICR ?? 'N/A' }}</pre>
                        </div>
                    </div>
                    <div class="proforma-details-box">
                        <div class="font-medium"><span class="material-icons-outlined">check_circle</span> Status:</div>
                        <div class="flex items-center">
                            <span
                                class="status-badge {{ $paymentRequest->status == 'completed' ? 'approved' : 'pending' }}">
                                {{ ucfirst($paymentRequest->status) }}
                            </span>
                        </div>
                    </div>
                    <div class="proforma-details-box">
                        <div class="font-medium"><span class="material-icons-outlined">add_circle</span>Created At:
                        </div>
                        <div class="flex items-center">
                            <pre>{{ $paymentRequest->created_at->format('Y-m-d') }}</pre>
                        </div>
                    </div>
                    <div class="proforma-details-box">
                        <div class="font-medium"><span class="material-icons-outlined">info</span> Description:</div>
                        <div class="flex items-center">
                            <pre>{{ $paymentRequest->description ?? 'N/A' }}</pre>
                        </div>
                    </div>
                    @if( $paymentRequest->payments->isNotEmpty())
                        <h5 class="text-xl font-semibold col-span-full mt-6 divider-attachment">Settlement:</h5>
                        <div class="proforma-details-box">
                            <div class="font-medium">
                                <span class="material-icons-outlined">tag</span> Reference No.:
                            </div>
                            <div class="flex items-center">
                                <pre>{{ $paymentRequest->payments->pluck('reference_number')->implode(' | ') }}</pre>
                            </div>
                        </div>
                        <div class="proforma-details-box">
                            <div class="font-medium"><span class="material-icons-outlined">person</span> Payer(s):</div>
                            <div class="flex items-center">
                                <pre>{{ $paymentRequest->payments->pluck('payer')->implode(' | ') }}</pre>
                            </div>
                        </div>
                        <div class="proforma-details-box">
                            <div class="font-medium"><span class="material-icons-outlined">calendar_today</span>
                                Transfer Dates:
                            </div>
                            <div class="flex items-center">
                                <pre>{{ $paymentRequest->payments->pluck('date')->map(fn ($date) =>$date?->format('Y-m-d'))->implode(' | ') }}</pre>
                            </div>
                        </div>
                        <div class="proforma-details-box">
                            <div class="font-medium"><span class="material-icons-outlined">money</span> Total Amount:
                            </div>
                            <div class="flex items-center">
                                <pre>{{ $paymentRequest->payments->contains('currency', 'USD') ? '$' : '' }}{{ number_format($paymentRequest->payments->sum('amount'), 2) }}</pre>
                            </div>
                        </div>
                    @endif
                    <h5 class="text-xl font-semibold col-span-full mt-6 divider-attachment">Transfer Receipt(s):</h5>
                    @php
                        $attachments = !isset($paymentRequest->order_id)
                        ? $selectedProforma->associatedPaymentRequests->flatMap->payments->flatMap->attachments
                        : $selectedProforma->orders->flatMap->paymentRequests->firstWhere('id', $paymentRequest->id)->payments->flatMap->attachments;
                    @endphp
                    @foreach ( $attachments as $attachment)
                        <div class="flex items-center justify-between p-2 rounded border status-badge approved ">
                            <div class="flex items-center gap-2">
                                <span class="material-icons-outlined">attachment</span>
                                @if ($attachment && $attachment->file_path)
                                    <a href="{{ asset($attachment->file_path) }}" target="_blank" class="underline">
                                        <span class="text-lg font-medium">{{ $attachment->name }}</span>
                                    </a>
                                @else
                                    <span class="text-lg font-medium"></span>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endforeach
@endif

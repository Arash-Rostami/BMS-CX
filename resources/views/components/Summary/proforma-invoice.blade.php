<!-- Header -->
<h3 class="mb-3">
    <span class="material-icons-outlined text-sm insight">receipt_long</span>
    <span class="text-2xl font-semibold mb-4">Proforma Invoice</span>
</h3>

<!-- Accordion Container -->
<div x-data="{ open: false }" class="border rounded-lg shadow-lg">
    <!-- Accordion Header -->
    <button @click="open = !open"
            class="w-full flex justify-between items-center my-dark-class px-4 py-3 text-left text-lg font-semibold rounded-xl">
        <span>No: {{ $selectedProforma->reference_number ?? 'N/A' }}</span>
        <span class="material-icons-outlined" x-show="open">expand_less</span>
        <span class="material-icons-outlined" x-show="!open">expand_more</span>
    </button>

    <!-- Accordion Content -->
    <div x-show="open" x-collapse class="p-4 border-t">
        <div class="proforma-details-grid">
            <div class="proforma-details-box">
                <div class="font-medium">
                    <span class="material-icons-outlined">tag</span> Reference No.:
                </div>
                <pre>{{ $selectedProforma->reference_number ?? 'N/A' }}</pre>
            </div>
            <div class="proforma-details-box">
                <div class="font-medium">
                    <span class="material-icons-outlined">description</span> Contract No.:
                </div>
                <pre>{{ $selectedProforma->contract_number ?? 'N/A' }}</pre>
            </div>
            <div class="proforma-details-box">
                <div class="font-medium">
                    <span class="material-icons-outlined">file_copy</span> Proforma No.:
                </div>
                <pre>{{ $selectedProforma->proforma_number }}</pre>
            </div>
            <div class="proforma-details-box">
                <div class="font-medium">
                    <span class="material-icons-outlined">event</span> Proforma Date:
                </div>
                <pre>{{ $selectedProforma->proforma_date?->format('Y-m-d') ?? 'N/A' }}</pre>
            </div>
            <div class="proforma-details-box">
                <div class="font-medium">
                    <span class="material-icons-outlined">percent</span> Percentage:
                </div>
                <pre>{{ $selectedProforma->percentage ?? 'N/A' }}</pre>
            </div>
            <div class="proforma-details-box">
                <div class="font-medium">
                    <span class="material-icons-outlined">attach_money</span> Price:
                </div>
                <pre>{{ number_format($selectedProforma->price, 2) ?? 'N/A' }}</pre>
            </div>
            <div class="proforma-details-box">
                <div class="font-medium">
                    <span class="material-icons-outlined">view_module</span> Quantity:
                </div>
                <pre>{{ $selectedProforma->quantity ?? 'N/A' }}</pre>
            </div>
            <div class="proforma-details-box">
                <div class="font-medium">
                    <span class="material-icons-outlined">category</span> Part:
                </div>
                <pre>{{ $selectedProforma->part ?? 'N/A' }}</pre>
            </div>
            <div class="proforma-details-box">
                <div class="font-medium">
                    <span class="material-icons-outlined">person</span> Buyer:
                </div>
                <pre>{{ $selectedProforma->buyer?->name ?? 'N/A' }}</pre>
            </div>
            <div class="proforma-details-box">
                <div class="font-medium">
                    <span class="material-icons-outlined">business</span> Supplier:
                </div>
                <pre>{{ $selectedProforma->supplier?->name ?? 'N/A' }}</pre>
            </div>
            <div class="proforma-details-box">
                <div class="font-medium">
                    <span class="material-icons-outlined">local_offer</span> Category:
                </div>
                <pre>{{ $selectedProforma->category?->name ?? 'N/A' }}</pre>
            </div>
            <div class="proforma-details-box">
                <div class="font-medium">
                    <span class="material-icons-outlined">shopping_cart</span> Product:
                </div>
                <pre>{{ $selectedProforma->product?->name ?? 'N/A' }}</pre>
            </div>
            <div class="proforma-details-box">
                <div class="font-medium">
                    <span class="material-icons-outlined">straighten</span> Grade:
                </div>
                <pre>{{ $selectedProforma->grade?->name ?? 'N/A' }}</pre>
            </div>
            <div class="proforma-details-box">
                <div class="font-medium">
                    <span class="material-icons-outlined">person_add</span> Created | Assignee:
                </div>
                <pre>{{ $selectedProforma->user?->first_name ?? 'N/A' }} | {{ $selectedProforma->assignee?->first_name ?? 'N/A' }}</pre>
            </div>
            <div class="proforma-details-box">
                <div class="font-medium">
                    <span class="material-icons-outlined">check_circle</span> Status:
                </div>
                <span class="status-badge {{ $selectedProforma->status == 'approved' ? 'approved' : 'pending' }}">
          {{ ucfirst($selectedProforma->status == 'approved' ? 'ongoing' : $selectedProforma->status) }}
        </span>
            </div>
            @if ($selectedProforma->extra && isset($selectedProforma->extra['port']) && !empty($selectedProforma->extra['port']))
                <div class="proforma-details-box">
                    <div class="font-medium">
                        <span class="material-icons-outlined">add_circle_outline</span> Extra:
                    </div>
                    <pre>{{ implode(", ", $selectedProforma->extra['port']) }}</pre>
                </div>
            @endif
            @if ($selectedProforma->details && isset($selectedProforma->details['notes']) && !empty($selectedProforma->details['notes']))
                <div class="proforma-details-box">
                    <div class="font-medium">
                        <span class="material-icons-outlined">info</span> Details:
                    </div>
                    <pre>@if (is_array($selectedProforma->details['notes'])){{ implode(", ", $selectedProforma->details['notes']) }} @else {{ $selectedProforma->details['notes'] }}@endif</pre>
                </div>
            @endif

            <h5 class="text-xl font-semibold col-span-full mt-6 divider-attachment">Attachments:</h5>
            @foreach ($proformaAttachmentNames  as $attachmentName)
                @php($attachment = $selectedProforma->attachments->firstWhere('name', $attachmentName))
                <div class="flex items-center justify-between p-2 rounded border
                {{ $attachment ? 'status-badge approved' : 'status-badge cancelled' }}">
                    <div class="flex items-center gap-2">
                        <span class="material-icons-outlined">attachment</span>
                        @if ($attachment && $attachment->file_path)
                            <a href="{{ asset($attachment->file_path) }}" target="_blank" class="underline">
                                <span class="text-lg font-medium">{{ $attachment->name }}</span>
                            </a>
                        @else
                            <span class="text-lg cursor-help font-medium"
                                  title="Not yet uploaded!">{{ $attachmentName }}</span>
                        @endif
                    </div>
                </div>
            @endforeach
            @if ($proformaAttachmentNames->isEmpty())
                <div class="p-2 rounded border bg-gray-100 border-gray-400 text-gray-600">
                    No attachments found!
                </div>
            @endif
        </div>
    </div>
</div>

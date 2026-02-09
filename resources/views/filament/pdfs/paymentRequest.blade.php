<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Request Details</title>
    <style>
        body {
            font-family: DejaVu Sans, 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 1px;
            transform: scale(0.8);
        }

        .monospace {
            font-family: monospace !important;
            font-size: 13px;
        }

        .container {
            background-color: #fff;
            border-radius: 8px;
            padding: 20px;
            max-width: 900px;
            margin: auto;
            page-break-inside: avoid;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }

        .logo {
            color: #2980b9;
            font-size: 28px;
            text-align: left;
            font-weight: bold;
            border-bottom: 1px solid #ddd;
            padding-bottom: 10px;
        }

        .header .details {
            text-align: right;
        }

        .header .details h1 {
            font-size: 24px;
            margin: 0 0 10px 0;
            color: #2980b9;
        }

        .header .details div {
            font-size: 12px;
            color: #7f8c8d;
            margin-bottom: 3px;
        }

        h3 {
            margin: 1.5em 0 0.5em;
            color: #2980b9;
            font-size: 16px;
            padding-bottom: .5em;
            border-bottom: 1px solid #ddd;
        }

        .table {
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            page-break-inside: auto;
            table-layout: fixed;
        }

        .table th {
            width: 35%;
            background-color: #f2f2f2;
            font-weight: bold;
            color: #333;
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
            vertical-align: top;
        }

        .table td {
            width: 65%;
            border: none;
            border-bottom: 1px solid #eee;
            padding: 12px;
            text-align: left;
            color: #2c3e50;
            font-family: "Courier New", Courier, monospace;
            vertical-align: top;
        }

        .table tr:nth-child(even) {
            background-color: #fbfbfb;
        }

        .table tr:last-child th,
        .table tr:last-child td {
            border-bottom: none;
        }

        .table tr {
            page-break-inside: avoid;
            page-break-after: auto;
        }

        .total-section {
            margin-top: 20px;
            text-align: right;
        }

        .total-label {
            font-weight: bold;
            font-size: 16px;
            color: #555;
            margin-bottom: 5px;
        }

        .total-value {
            font-size: 16px;
            font-family: "Courier New", Courier, monospace;
            color: #2c3e50;
        }

        .footer {
            text-align: center;
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 30px;
            padding-top: 20px;
            border-top: 1px solid #ddd;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="logo">BMS</div>
        <div class="details">
            <h1>Payment Request</h1>
            @if($record->reference_number ?? null)
                <div>Reference No.: {{ $record->reference_number }}</div>
            @endif
            @if($record->sequential_id ?? null)
                <div>Tracking Ref. No.: {{ $record->sequential_id }}</div>
            @endif
            <div>Print Date: {{ now()->format('M d, Y') }}</div>
            @if($record->status ?? null)
                <div>Status: {{ ucfirst($record->status) }}</div>
            @endif
        </div>
    </div>

    <h3>General Information</h3>
    <table class="table">
        @if($record->department->name ?? null)
            <tr>
                <th>Department</th>
                <td>{{ $record->department->name }}</td>
            </tr>
        @endif
        @if($record->extra['costCenter'] ?? $record->department->name ?? null)
            <tr>
                <th>Cost Center</th>
                <td>{{ $record->extra['costCenter'] ?? $record->department->name }}</td>
            </tr>
        @endif
        @if($record->reason->reason ?? null)
            <tr>
                <th>Reason for Payment</th>
                <td>{{ $record->reason->reason }}</td>
            </tr>
        @endif
        @if($record->type_of_payment ?? null)
            <tr>
                <th>Payment Type</th>
                <td>{{ ucfirst(str_replace('_', ' ', $record->type_of_payment)) }}</td>
            </tr>
        @endif
        @if($record->deadline ?? null)
            <tr>
                <th>Deadline</th>
                <td>{{ $record->deadline->format('Y-m-d') }}</td>
            </tr>
        @endif
        @if($record->purpose ?? null)
            <tr>
                <th>Purpose</th>
                <td>{{ $record->purpose }}</td>
            </tr>
        @endif
    </table>

    <h3>Beneficiary Information</h3>
    <table class="table">
        @if(($record->supplier->name ?? null) && ($record->beneficiary_name ?? null) == 'supplier')
            <tr>
                <th>Supplier</th>
                <td>{{ $record->supplier->name }}</td>
            </tr>
        @endif
        @if(($record->contractor->name ?? null) && ($record->beneficiary_name ?? null) == 'contractor')
            <tr>
                <th>Contractor</th>
                <td>{{ $record->contractor->name }}</td>
            </tr>
        @endif
        @if(($record->payee->name ?? null) && ($record->beneficiary_name ?? null) == 'payee')
            <tr>
                <th>Beneficiary</th>
                <td>{{ $record->payee->name }}</td>
            </tr>
        @endif
        @if($record->recipient_name ?? null)
            <tr>
                <th>Recipient Name</th>
                <td>{{ $record->recipient_name }}</td>
            </tr>
        @endif
        @if($record->beneficiary_address ?? null)
            <tr>
                <th>Recipient Address</th>
                <td>{{ $record->beneficiary_address }}</td>
            </tr>
        @endif
    </table>

    <h3>Banking Details</h3>
    <table class="table">
        @if($record->bank_name ?? null)
            <tr>
                <th>Bank Name</th>
                <td>{{ $record->bank_name }}</td>
            </tr>
        @endif
        @if($record->bank_address ?? null)
            <tr>
                <th>Bank Address</th>
                <td>{{ $record->bank_address }}</td>
            </tr>
        @endif
        @if($record->extra['paymentMethod'] ?? null)
            <tr>
                <th>Payment Method</th>
                <td>{{ ['sheba' => 'SHEBA', 'bank_account' => 'Bank Account', 'card_transfer' => 'Card Transfer', 'cash' => 'Cash'][$record->extra['paymentMethod']] ?? 'Undefined' }}</td>
            </tr>
        @endif
        @if($record->account_number ?? null)
            <tr>
                <th>Account Number</th>
                <td>{{ $record->account_number }}</td>
            </tr>
        @endif
        @if($record->swift_code ?? null)
            <tr>
                <th>Swift Code</th>
                <td>{{ $record->swift_code }}</td>
            </tr>
        @endif
        @if($record->IBAN ?? null)
            <tr>
                <th>IBAN Code</th>
                <td>{{ $record->IBAN }}</td>
            </tr>
        @endif
        @if($record->IFSC ?? null)
            <tr>
                <th>IFSC Code</th>
                <td>{{ $record->IFSC }}</td>
            </tr>
        @endif
        @if($record->MICR ?? null)
            <tr>
                <th>MICR Code</th>
                <td>{{ $record->MICR }}</td>
            </tr>
        @endif
    </table>

    <h3>Payment Information</h3>
    <table class="table">
        @if($record->currency ?? null)
            <tr>
                <th>Currency</th>
                <td>{{ $record->currency }}</td>
            </tr>
        @endif
        @if($record->requested_amount ?? null)
            <tr>
                <th>Requested Amount</th>
                <td>{{ number_format($record->requested_amount, 2) }}</td>
            </tr>
        @endif
        @if($record->total_amount ?? null)
            <tr>
                <th>Total Amount</th>
                <td>{{ number_format($record->total_amount, 2) }}</td>
            </tr>
        @endif
        @if($record->proforma_invoice_number ?? null)
            <tr>
                <th>Proforma Invoice Number</th>
                <td>{{ $record->proforma_invoice_number }}</td>
            </tr>
        @endif
        @if($record->case_number ?? null)
            <tr>
                <th>Case/Contract Number</th>
                <td>{{ $record->case_number }}</td>
            </tr>
        @endif
        @if(in_array($record->type_of_payment ?? '', ['partial', 'balance', 'other']) && ($record->order ?? null))
            <tr>
                <th>Order</th>
                <td>
                    {{ $record->order->product->name ?? 'N/A' }}
                    ({{ $record->order->grade->name ?? 'N/A' }})
                    Part: {{ $record->order->part ?? 'N/A' }}
                </td>
            </tr>
            @if($record->order->invoice_number ?? null)
                <tr>
                    <th>Contract Number</th>
                    <td>{{ $record->order->invoice_number }}</td>
                </tr>
            @endif
        @endif
        @if($record->description ?? null)
            <tr>
                <th>Description</th>
                <td>{{ $record->description }}</td>
            </tr>
        @endif
    </table>
    @php
        $proformas = collect();

        if (in_array($record->type_of_payment ?? '', ['partial', 'balance', 'other'])) {
            if ($record->order?->proformaInvoice) {
                $proformas->push($record->order->proformaInvoice);
            }
        }
        elseif (($record->type_of_payment ?? '') === 'advance') {
            if ($record->associatedProformaInvoices?->isNotEmpty()) {
                $proformas = $record->associatedProformaInvoices;
            }
        }
    @endphp

    @if($proformas->isNotEmpty())
        <h3>Associated Proforma Invoice{{ $proformas->count() > 1 ? 's' : '' }}</h3>
        @foreach($proformas as $pi)
            <table class="table">
                @if($pi->proforma_number ?? null)
                    <tr>
                        <th>Proforma Number</th>
                        <td>{{ $pi->proforma_number }}</td>
                    </tr>
                @endif
                @if($pi->proforma_date ?? null)
                    <tr>
                        <th>Proforma Date</th>
                        <td>{{ $pi->proforma_date?->format('M d, Y') }}</td>
                    </tr>
                @endif
                @if($pi->contract_number ?? null)
                    <tr>
                        <th>Contract Number</th>
                        <td>{{ $pi->contract_number }}</td>
                    </tr>
                @endif
                @if($pi->product?->name)
                    <tr>
                        <th>Product</th>
                        <td>{{ $pi->product->name }}</td>
                    </tr>
                @endif
                @if($pi->grade?->name)
                    <tr>
                        <th>Grade</th>
                        <td>{{ $pi->grade->name }}</td>
                    </tr>
                @endif
                @if($pi->quantity ?? null)
                    <tr>
                        <th>Quantity (mt)</th>
                        <td>{{ number_format($pi->quantity, 2) }}</td>
                    </tr>
                @endif
                @if($pi->price ?? null)
                    <tr>
                        <th>Unit Price</th>
                        <td>{{ number_format($pi->price, 2) }}</td>
                    </tr>
                @endif
                @if($pi->buyer?->name)
                    <tr>
                        <th>Buyer</th>
                        <td>{{ $pi->buyer->name }}</td>
                    </tr>
                @endif
                @if($pi->supplier?->name)
                    <tr>
                        <th>Supplier</th>
                        <td>{{ $pi->supplier->name }}</td>
                    </tr>
                @endif
            </table>
        @endforeach
    @endif


    @if($record->requested_amount ?? $record->total_amount ?? null)
        <div class="total-section">
            <div class="total-label">Total Payable:</div>
            <div class="total-value">
                {{ number_format($record->requested_amount ?? 0, 2) }} {{ $record->currency ?? '' }}
                @if(($record->requested_amount ?? 0) != ($record->total_amount ?? 0) && ($record->total_amount ?? null))
                    from total of {{ number_format($record->total_amount, 2) }} {{ $record->currency ?? '' }}
                @endif
            </div>
        </div>
    @endif

    <div class="footer">
        Created on {{ $record->created_at ? $record->created_at->format('M d, Y') : 'Undefined' }}
        by {{ $record->extra['made_by'] ?? 'Undefined' }}
        <br>
        BMS print preview service
    </div>
</div>
</body>
</html>

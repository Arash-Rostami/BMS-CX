<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Request Details</title>
    <style>

        body {
            font-family: 'Arial', sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 20px;
        }

        .container {
            background-color: #fff;
            border-radius: 8px;
            padding: 40px;
            max-width: 900px;
            margin: auto;
            box-shadow: 0 2px 12px rgba(0, 0, 0, 0.1);
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .header img {
            max-height: 50px;
        }

        .header .details {
            text-align: right;
        }

        .header .details h1 {
            font-size: 24px;
            margin: 0;
            color: #2980b9;
        }

        .logo {
            color: #2980b9;
            font-size: 28px;
            text-align: left;
            font-weight: bold;
            margin-bottom: 30px;
            border-bottom: 1px solid #ddd;
        }

        .header .details div {
            font-size: 12px;
            color: #7f8c8d;
        }

        h2 {
            color: #34495e;
            font-size: 18px;
            margin: 40px 0 20px;
            border-bottom: 2px solid #ddd;
            padding-bottom: 10px;
        }

        .details-section {
            margin-bottom: 30px;
        }

        .details-section .label {
            font-weight: bold;
            color: #555;
        }

        .details-section .value {
            color: #2c3e50;
            font-family: "Courier New", Courier, monospace;
        }

        .beneficiary {
            display: flex;
            justify-content: space-between;
        }

        .beneficiary .beneficiary-details,
        .beneficiary .bank-details {
            width: 80%;
        }

        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
        }

        .table th, .table td {
            padding: 12px;
            border: 1px solid #ddd;
            text-align: left;
        }

        .table th {
            background-color: #f2f2f2;
            font-weight: bold;
            color: #333;
        }

        .table td.value {
            font-family: "Courier New", Courier, monospace;
            color: #2c3e50;
        }

        .total {
            display: flex;
            justify-content: flex-end;
            margin-top: 20px;
        }

        .total div {
            width: 300px;
        }

        .total .label {
            font-weight: bold;
            font-size: 16px;
            color: #555;
        }

        div.total + div {
            font-size: 16px;
            font-family: "Courier New", Courier, monospace;
            color: #2c3e50;
            text-align: center;
        }


        .footer, .final {
            text-align: center;
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 40px;
        }
    </style>
</head>
<body>
<div class="container">
    <div class="header">
        <div class="logo">BMS</div>
        <div class="details">
            <h1>Payment Request</h1>
            @if($record->reference_number)
                <div>Reference No.: {{ $record->reference_number }}</div>
            @endif
            @if($record->sequential_id)
                <div>Tracking Ref. No.: {{ $record->sequential_id }}</div>
            @endif
            <div>Print Date: {{ now()->format('M d, Y') }}</div>
            @if($record->status)
                <div>Status: {{ ucfirst($record->status) }}</div>
            @endif
        </div>
    </div>

    <h2>General Information</h2>
    <div class="details-section">
        <table class="table">
            @if($record->department?->name)
                <tr>
                    <th>Department</th>
                    <td>{{ $record->department->name }}</td>
                </tr>
            @endif

            @if($record->extra['costCenter'] ?? $record->department?->name)
                <tr>
                    <th>Cost Center</th>
                    <td>{{ $record->extra['costCenter'] ?? $record->department?->name }}</td>
                </tr>
            @endif

            @if($record->reason?->reason)
                <tr>
                    <th>Reason for Payment</th>
                    <td>{{ $record->reason->reason }}</td>
                </tr>
            @endif

            @if($record->type_of_payment)
                <tr>
                    <th>Payment Type</th>
                    <td>{{ ucfirst($record->type_of_payment) }}</td>
                </tr>
            @endif

            @if($record->deadline)
                <tr>
                    <th>Deadline</th>
                    <td>{{ $record->deadline->format('Y-m-d') }}</td>
                </tr>
            @endif

            @if($record->purpose)
                <tr>
                    <th>Purpose</th>
                    <td>{{ $record->purpose }}</td>
                </tr>
            @endif
        </table>
    </div>

    <h2>Beneficiary Information</h2>
    <table class="table">
        @if($record->supplier?->name && $record->beneficiary_name == 'supplier')
            <tr>
                <th>Supplier</th>
                <td>{{ $record->supplier->name }}</td>
            </tr>
        @endif

        @if($record->contractor?->name && $record->beneficiary_name == 'contractor')
            <tr>
                <th>Contractor</th>
                <td>{{ $record->contractor->name }}</td>
            </tr>
        @endif

        @if($record->payee?->name && $record->beneficiary_name == 'payee')
            <tr>
                <th>Beneficiary</th>
                <td>{{ $record->payee->name }}</td>
            </tr>
        @endif
        @if($record->recipient_name)
            <tr>
                <th>Recipient Name</th>
                <td>{{ $record->recipient_name }}</td>
            </tr>
        @endif

        @if($record->beneficiary_address)
            <tr>
                <th>Recipient Address</th>
                <td>{{ $record->beneficiary_address }}</td>
            </tr>
        @endif

    </table>

    <div class="bank-details">
        <div class="details-section">
            @if($record->bank_name)
                <div><span class="label">Bank Name:</span> <span class="value">{{ $record->bank_name }}</span></div>
            @endif

            @if($record->bank_address)
                <div><span class="label">Bank Address:</span> <span class="value">{{ $record->bank_address }}</span>
                </div>
            @endif

            @if($record->extra['paymentMethod'])
                <div>
                    <span class="label">Payment Method: </span>
                    <span class="value">
            {{
                ['sheba' => 'SHEBA','bank_account' => 'Bank Account','card_transfer' => 'Card Transfer','cash' => 'Cash'][$record->extra['paymentMethod']] ?? 'Undefined'
            }}</span>
                </div>
            @endif


            @if($record->account_number)
                <div><span class="label">Account Number:</span> <span class="value">{{ $record->account_number }}</span>
                </div>
            @endif

            @if($record->swift_code)
                <div><span class="label">Swift Code:</span> <span class="value">{{ $record->swift_code }}</span></div>
            @endif

            @if($record->IBAN)
                <div><span class="label">IBAN Code:</span> <span class="value">{{ $record->IBAN }}</span></div>
            @endif

            @if($record->IFSC)
                <div><span class="label">IFSC Code:</span> <span class="value">{{ $record->IFSC }}</span></div>
            @endif

            @if($record->MICR)
                <div><span class="label">MICR Code:</span> <span class="value">{{ $record->MICR }}</span></div>
            @endif
        </div>
    </div>

    <h2>Payment Information</h2>
    <table class="table">
        @if($record->requested_amount)
            <tr>
                <th class="label">Requested Amount</th>
                <td class="value">{{ number_format($record->requested_amount, 2) }}</td>
            </tr>
        @endif

        @if($record->total_amount)
            <tr>
                <th class="label">Total Amount</th>
                <td class="value">{{ number_format($record->total_amount, 2) }}</td>
            </tr>
        @endif

        @if($record->currency)
            <tr>
                <th class="label">Currency</th>
                <td class="value">{{ $record->currency }}</td>
            </tr>
        @endif

        @if($record->proforma_invoice_number)
            <tr>
                <th class="label">Proforma Invoice Number</th>
                <td class="value">{{ $record->proforma_invoice_number }}</td>
            </tr>
        @endif

        @if(in_array($record->type_of_payment, ['partial', 'balance', 'other']) && $record->order)
            <tr>
                <th class="label">Order</th>
                <td class="value">
                    {{ $record->order?->product?->name }}
                    ({{ $record->order?->grade?->name ?? 'N/A' }})
                    Part: {{ $record->order?->part }}
                </td>
            </tr>
        @endif

        @if($record->case_number)
            <tr>
                <th>Case/Contract Number</th>
                <td>{{ $record->case_number }}</td>
            </tr>
        @endif

        @if($record->description)
            <tr>
                <th class="label">Description</th>
                <td class="value">{{ $record->description }}</td>
            </tr>
        @endif
    </table>

    @if($record->requested_amount || $record->total_amount)
        <div class="total">
            <div class="label">Total Payable:</div>
        </div>
        <div class="value">
            {{ number_format($record->requested_amount ?? 0, 2) }} from total
            of {{ number_format($record->total_amount ?? 0, 2) }}
        </div>
    @endif

    <div class="footer">
        Created on {{ $record->created_at?->format('M d, Y') ?? 'Undefined' }}
        by {{ $record->extra['made_by'] ?? 'Undefined' }}
        <br>
        BMS print preview service
    </div>
</div>
</body>
</html>

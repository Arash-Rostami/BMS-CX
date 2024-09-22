<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Request Details</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            background-color: #E6E6E6;
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

        .total .value {
            font-size: 16px;
            font-family: "Courier New", Courier, monospace;
            color: #2c3e50;
            text-align: right;
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
            <div>Reference #: {{ $record->reference_number }}</div>
            <div>Print Date: {{ now()->format('M d, Y') }}</div>
            <div>Status: {{ ucfirst($record->status) }}</div>
        </div>
    </div>

    <h2>General Information</h2>
    <div class="details-section">
        <table class="table">
            <tr>
                <th class="label">Department</th>
                <td class="value">{{ $record->department->name }}</td>
            </tr>
            <tr>
                <th class="label">Cost Center</th>
                <td class="value">{{ $record->extra['costCenter'] ?? $record->department->name }}</td>
            </tr>
            <tr>
                <th class="label">Reason for Payment</th>
                <td class="value">{{ $record->reason->reason }}</td>
            </tr>
            <tr>
                <th class="label">Purpose</th>
                <td class="value">{{ $record->purpose }}</td>
            </tr>
            <tr>
                <th class="label">Payment Type</th>
                <td class="value">{{ $record->type_of_payment }}</td>
            </tr>
        </table>
    </div>

    <h2>Beneficiary Information</h2>
    <div class="beneficiary">
        <div class="beneficiary-details">
            <div class="details-section">
                <div><span class="label">Beneficiary Name:</span> <span
                        class="value">{{ $record->recipient_name }}</span></div>

                @if($record->beneficiary_name == 'supplier')
                    <div><span class="label">Supplier:</span> <span
                            class="value">{{ optional($record->supplier)->name }}</span></div>
                @elseif($record->beneficiary_name == 'contractor')
                    <div><span class="label">Contractor:</span> <span
                            class="value">{{ optional($record->contractor)->name }}</span></div>
                @elseif($record->beneficiary_name == 'payee')
                    <div><span class="label">Payee:</span> <span
                            class="value">{{ optional($record->payee)->name }}</span></div>
                @endif

                <div><span class="label">Beneficiary Address:</span> <span
                        class="value">{{ $record->beneficiary_address }}</span></div>
            </div>
        </div>

        <div class="bank-details">
            <div class="details-section">
                <div><span class="label">Bank Name:</span> <span class="value">{{ $record->bank_name }}</span></div>
                <div><span class="label">Bank Address:</span> <span class="value">{{ $record->bank_address }}</span>
                </div>
                <div><span class="label">Account Number:</span> <span class="value">{{ $record->account_number }}</span>
                </div>
                <div><span class="label">Swift Code:</span> <span class="value">{{ $record->swift_code }}</span></div>
                <div><span class="label">IBAN Code:</span> <span class="value">{{ $record->IBAN }}</span></div>
                <div><span class="label">IFSC Code:</span> <span class="value">{{ $record->IFSC }}</span></div>
                <div><span class="label">MICR Code:</span> <span class="value">{{ $record->MICR }}</span></div>
            </div>
        </div>
    </div>

    <h2>Payment Information</h2>
    <table class="table">
        <tr>
            <th class="label">Requested Amount</th>
            <td class="value">{{ number_format($record->requested_amount, 2) }}</td>
        </tr>
        <tr>
            <th class="label">Total Amount</th>
            <td class="value">{{ number_format($record->total_amount, 2) }}</td>
        </tr>
        <tr>
            <th class="label">Currency</th>
            <td class="value">{{ $record->currency }}</td>
        </tr>
        <tr>
            <th class="label">Proforma Invoice Number</th>
            <td class="value">{{ $record->proforma_invoice_number }}</td>
        </tr>
        <tr>
            <th class="label">Description</th>
            <td class="value">{{ $record->description }}</td>
        </tr>
    </table>

    <div class="total">
        <div>
            <div class="label">Total Payable:</div>
            <div class="value">{{ number_format($record->total_amount, 2) }}</div>
        </div>
    </div>

    <div class="footer">
        Created on {{ optional($record->created_at)->format('M d, Y') }} by {{ $record->extra['made_by'] }}
        <br>
        BMS print preview service
    </div>
</div>
</body>
</html>

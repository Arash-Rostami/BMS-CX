<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Details</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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

        .logo {
            color: #2980b9;
            font-size: 28px;
            text-align: left;
            font-weight: bold;
            margin-bottom: 30px;
            border-bottom: 1px solid #ddd;
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

        .footer {
            text-align: center;
            font-size: 12px;
            color: #7f8c8d;
            margin-top: 40px;
        }
    </style>
</head>
<body>
<div class="container">
    <!-- Header -->
    <div class="header">
        <div class="logo">BMS</div>
        <div class="details">
            <h1>Payment Details</h1>
            @if($record->reference_number)
                <div>Reference #: {{ $record->reference_number }}</div>
            @endif
            <div>Print Date: {{ now()->format('M d, Y') }}</div>
        </div>
    </div>

    <!-- Payment Information -->
    <h2>Payment Information</h2>
    <table class="table">
        @if($record->paymentRequests->isNotEmpty())
            <tr>
                <th class="label">Payment Request</th>
                <td class="value">{{ implode(', ', $record->paymentRequests->pluck('reference_number')->toArray()) }}</td>
            </tr>
        @endif
        @if($record->currency)
            <tr>
                <th class="label">Currency</th>
                <td class="value">{{ $record->currency }}</td>
            </tr>
        @endif
        @if($record->amount)
            <tr>
                <th class="label">Amount</th>
                <td class="value">{{ number_format($record->amount, 2) }}</td>
            </tr>
        @endif
        @if($record->payer)
            <tr>
                <th class="label">Payer</th>
                <td class="value">{{ $record->payer }}</td>
            </tr>
        @endif
    </table>

    <!-- Additional Payment Information -->
    <h2>Additional Information</h2>
    <table class="table">
        @if($record->paymentRequests->first()?->department?->name)
            <tr>
                <th class="label">Department</th>
                <td class="value">{{ $record->paymentRequests->first()->department->name }}</td>
            </tr>
        @endif

        @if($record->paymentRequests->first()?->costCenter?->code)
            <tr>
                <th class="label">Cost Center</th>
                <td class="value">{{ $record->paymentRequests->first()->costCenter->code }}</td>
            </tr>
        @endif

        @if($record->paymentRequests->first()?->recipient_name)
            <tr>
                <th class="label">Beneficiary Name</th>
                <td class="value">{{ ucfirst($record->paymentRequests->first()->recipient_name) }}</td>
            </tr>
        @endif

        @if($record->paymentRequests->first()?->requested_amount)
            <tr>
                <th class="label">Requested Amount</th>
                <td class="value">{{ number_format($record->paymentRequests->first()->requested_amount, 2) }}</td>
            </tr>
        @endif

        @if($record->paymentRequests->first()?->deadline)
            <tr>
                <th class="label">Deadline</th>
                <td class="value">{{ optional($record->paymentRequests->first()->deadline)->format('M d, Y') }}</td>
            </tr>
        @endif

        <tr>
            <th class="label">Process Status</th>
            <td class="value">{{ $record->process_status }}</td>
        </tr>

        @if($record->transaction_id)
            <tr>
                <th class="label">Transaction ID</th>
                <td class="value">{{ $record->transaction_id }}</td>
            </tr>
        @endif
        @if($record->date)
            <tr>
                <th class="label">Transfer Date</th>
                <td class="value">{{ $record->date->format('M d, Y') }}</td>
            </tr>
        @endif
        @if($record->extra['remainderSum'] ?? false)
            <tr>
                <th class="label">Remainder Sum</th>
                <td class="value">{{ number_format($record->extra['remainderSum'], 2) }}</td>
            </tr>
        @endif
        @if($record->extra['balanceStatus'] ?? false)
            <tr>
                <th class="label">Balance Status</th>
                <td class="value">{{ ucfirst($record->extra['balanceStatus']) }}</td>
            </tr>
        @endif
        @if($record->notes)
            <tr>
                <th class="label">Notes</th>
                <td class="value">{{ $record->notes }}</td>
            </tr>
        @endif
    </table>
    <div></div>


    <div class="footer">
        Made on {{ optional($record->created_at)->format('M d, Y') }} by {{ optional($record->user)->fullName }}
        <br>
        BMS print preview service
    </div>
</div>
</body>
</html>

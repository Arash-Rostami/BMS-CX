<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Details</title>
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
            <h1>Payment Details</h1>
            @if($record->reference_number ?? null)
                <div>Reference #: {{ $record->reference_number }}</div>
            @endif
            <div>Print Date: {{ now()->format('M d, Y') }}</div>
        </div>
    </div>

    <h3>Payment Information</h3>
    <table class="table">
        @if($record->paymentRequests->isNotEmpty() ?? false)
            <tr>
                <th>Payment Request</th>
                <td>{{ implode(', ', $record->paymentRequests->pluck('reference_number')->toArray()) }}</td>
            </tr>
        @endif
        @if($record->currency ?? null)
            <tr>
                <th>Currency</th>
                <td>{{ $record->currency }}</td>
            </tr>
        @endif
        @if($record->amount ?? null)
            <tr>
                <th>Amount</th>
                <td>{{ number_format($record->amount, 2) }}</td>
            </tr>
        @endif
        @if($record->payer ?? null)
            <tr>
                <th>Payer</th>
                <td>{{ $record->payer }}</td>
            </tr>
        @endif
    </table>

    <h3>Additional Information</h3>
    <table class="table">
        @if($record->paymentRequests->first()->department->name ?? null)
            <tr>
                <th>Department</th>
                <td>{{ $record->paymentRequests->first()->department->name }}</td>
            </tr>
        @endif
        @if($record->paymentRequests->first()->costCenter->code ?? null)
            <tr>
                <th>Cost Center</th>
                <td>{{ $record->paymentRequests->first()->costCenter->code }}</td>
            </tr>
        @endif
        @if($record->paymentRequests->first()->recipient_name ?? null)
            <tr>
                <th>Beneficiary Name</th>
                <td>{{ ucfirst($record->paymentRequests->first()->recipient_name) }}</td>
            </tr>
        @endif
        @if($record->paymentRequests->first()->requested_amount ?? null)
            <tr>
                <th>Requested Amount</th>
                <td>{{ number_format($record->paymentRequests->first()->requested_amount, 2) }}</td>
            </tr>
        @endif
        @if($record->paymentRequests->first()->deadline ?? null)
            <tr>
                <th>Deadline</th>
                <td>{{ $record->paymentRequests->first()->deadline->format('M d, Y') }}</td>
            </tr>
        @endif
        @if($record->process_status ?? null)
            <tr>
                <th>Process Status</th>
                <td>{{ $record->process_status }}</td>
            </tr>
        @endif
        @if($record->transaction_id ?? null)
            <tr>
                <th>Transaction ID</th>
                <td>{{ $record->transaction_id }}</td>
            </tr>
        @endif
        @if($record->date ?? null)
            <tr>
                <th>Transfer Date</th>
                <td>{{ $record->date->format('M d, Y') }}</td>
            </tr>
        @endif
        @if($record->extra['remainderSum'] ?? null)
            <tr>
                <th>Remainder Sum</th>
                <td>{{ number_format($record->extra['remainderSum'], 2) }}</td>
            </tr>
        @endif
        @if($record->extra['balanceStatus'] ?? null)
            <tr>
                <th>Balance Status</th>
                <td>{{ ucfirst($record->extra['balanceStatus']) }}</td>
            </tr>
        @endif
        @if($record->notes ?? null)
            <tr>
                <th>Notes</th>
                <td>{{ $record->notes }}</td>
            </tr>
        @endif
    </table>

    <div class="footer">
        Made on {{ $record->created_at ? $record->created_at->format('M d, Y') : 'Undefined' }}
        by {{ $record->user->fullName ?? 'Undefined' }}
        <br>
        BMS print preview service
    </div>
</div>
</body>
</html>

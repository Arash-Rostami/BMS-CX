<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment Details</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
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
            <div>Reference #: {{ $record->reference_number }}</div>
            <div>Print Date: {{ now()->format('M d, Y') }}</div>
        </div>
    </div>

    <!-- Payment Information -->
    <h2>Payment Information</h2>
    <table class="table">
        <tr>
            <th class="label">Payment Request</th>
            <td class="value">{{ implode(', ', $record->paymentRequests->pluck('reference_number')->toArray()) }}</td>
        </tr>
        <tr>
            <th class="label">Currency</th>
            <td class="value">{{ $record->currency }}</td>
        </tr>
        <tr>
            <th class="label">Amount</th>
            <td class="value">{{ $record->amount }}</td>
        </tr>
        <tr>
            <th class="label">Payer</th>
            <td class="value">{{ $record->payer }}</td>
        </tr>
    </table>

    <!-- Notes Section -->
    <h2>Details</h2>
    <table class="table">
        <tr>
            <th class="label">Transaction ID</th>
            <td class="value">{{ $record->transaction_id }}</td>
        </tr>
        <tr>
            <th class="label">Transfer Date</th>
            <td class="value">{{ optional($record->date)->format('M d, Y') }}</td>
        </tr>
        <tr>
            <th class="label">Notes</th>
            <td class="value">{{ $record->notes ?? 'N/A' }}</td>
        </tr>
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

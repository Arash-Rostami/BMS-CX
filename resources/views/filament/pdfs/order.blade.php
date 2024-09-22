<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BMS Order Details</title>
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
            <h1>Order Details</h1>
            <div>Reference #: {{ $record->reference_number }}</div>
            <div>Proforma Number: {{ $record->proforma_number }}</div>
            <div>Proforma Date: {{ optional($record->proforma_date)->format('M d, Y') }}</div>
            <div>Status: {{ ucfirst($record->order_status) }}</div>
        </div>
    </div>

    <!-- General Information -->
    <h2>General Information</h2>
    <table class="table">
        <tr>
            <th class="label">Category</th>
            <td class="value">{{ $record->category->name }}</td>
        </tr>
        <tr>
            <th class="label">Product</th>
            <td class="value">{{ $record->product->name }}</td>
        </tr>
        <tr>
            <th class="label">Grade</th>
            <td class="value">{{ $record->grade->name }}</td>
        </tr>
        <tr>
            <th class="label">Buyer</th>
            <td class="value">{{ $record->party->buyer->name }}</td>
        </tr>
        <tr>
            <th class="label">Supplier</th>
            <td class="value">{{ $record->party->supplier->name }}</td>
        </tr>
    </table>

    <!-- Order Details -->
    <h2>Order Details</h2>
    <table class="table">
        <tr>
            <th class="label">Initial Quantity (mt)</th>
            <td class="value">{{ $record->orderDetail->buying_quantity }}</td>
        </tr>
        <tr>
            <th class="label">Provisional Quantity (mt)</th>
            <td class="value">{{ $record->orderDetail->provisional_quantity }}</td>
        </tr>
        <tr>
            <th class="label">Final Quantity (mt)</th>
            <td class="value">{{ $record->orderDetail->final_quantity }}</td>
        </tr>
        <tr>
            <th class="label">Percentage</th>
            <td class="value">{{ $record->orderDetail->extra['percentage'] ?? $record->proformaInvoice->percentage }}%
            </td>
        </tr>
        <tr>
            <th class="label">Initial Unit Price</th>
            <td class="value">{{ $record->orderDetail->buying_price }}</td>
        </tr>
        <tr>
            <th class="label">Provisional Unit Price</th>
            <td class="value">{{ $record->orderDetail->provisional_price }}</td>
        </tr>
        <tr>
            <th class="label">Final Unit Price</th>
            <td class="value">{{ $record->orderDetail->final_price }}</td>
        </tr>
        <tr>
            <th class="label">Currency</th>
            <td class="value">{{ $record->orderDetail->extra['currency'] }}</td>
        </tr>
    </table>

    <!-- Payment and Logistics Details -->
    <h2>Payment and Logistics</h2>
    <table class="table">
        <tr>
            <th class="label">Pre-payment</th>
            <td class="value">{{ $record->orderDetail->extra['initialPayment'] }}</td>
        </tr>
        <tr>
            <th class="label">Provisional Payment</th>
            <td class="value">{{ $record->orderDetail->extra['provisionalTotal'] }}</td>
        </tr>
        <tr>
            <th class="label">Final Payment</th>
            <td class="value">{{ $record->orderDetail->extra['finalTotal'] }}</td>
        </tr>
        <tr>
            <th class="label">Loading Deadline</th>
            <td class="value">{{ optional($record->logistic->loading_deadline)->format('M d, Y') }}</td>
        </tr>
        <tr>
            <th class="label">Number of Containers</th>
            <td class="value">{{ $record->logistic->number_of_containers }}</td>
        </tr>
        <tr>
            <th class="label">Ocean Freight</th>
            <td class="value">{{ $record->logistic->ocean_freight }}</td>
        </tr>
        <tr>
            <th class="label">Terminal Handling Charges</th>
            <td class="value">{{ $record->logistic->terminal_handling_charges }}</td>
        </tr>
    </table>

    <!-- BL and Declaration -->
    <h2>BL and Declaration</h2>
    <table class="table">
        <tr>
            <th class="label">Voyage Number</th>
            <td class="value">{{ $record->voyage_number }}</td>
        </tr>
        <tr>
            <th class="label">Declaration Number</th>
            <td class="value">{{ $record->declaration_number }}</td>
        </tr>
        <tr>
            <th class="label">Declaration Date</th>
            <td class="value">{{ optional($record->declaration_date)->format('M d, Y') }}</td>
        </tr>
        <tr>
            <th class="label">BL Number</th>
            <td class="value">{{ $record->BL_number }}</td>
        </tr>
        <tr>
            <th class="label">BL Date</th>
            <td class="value">{{ optional($record->BL_date)->format('M d, Y') }}</td>
        </tr>
    </table>

    <div class="footer">
        Created on {{ optional($record->created_at)->format('M d, Y') }} by {{ optional($record->user)->fullName }}
        <br>
        BMS print preview service
    </div>
</div>
</body>
</html>

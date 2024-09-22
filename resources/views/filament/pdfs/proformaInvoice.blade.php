<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BMS Pro forma Invoice Details</title>
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
            <h1>Pro forma Invoice Details</h1>
            <div>Reference #: {{ $record->reference_number }}</div>
            <div>Proforma Number: {{ $record->proforma_number }}</div>
            <div>Proforma Date: {{ optional($record->proforma_date)->format('M d, Y') }}</div>
            <div>Status: {{ ucfirst($record->status) }}</div>
        </div>
    </div>

    <!-- General Information -->
    <h2>General Information</h2>
    <table class="table">
        <tr>
            <th class="label">Contract Number</th>
            <td class="value">{{ $record->contract_number }}</td>
        </tr>
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
    </table>

    <!-- Buyer and Supplier Information -->
    <h2>Buyer and Supplier Information</h2>
    <table class="table">
        <tr>
            <th class="label">Buyer</th>
            <td class="value">{{ $record->buyer->name }}</td>
        </tr>
        <tr>
            <th class="label">Supplier</th>
            <td class="value">{{ $record->supplier->name }}</td>
        </tr>
    </table>

    <!-- Pricing and Quantity Information -->
    <h2>Pricing and Quantity</h2>
    <table class="table">
        <tr>
            <th class="label">Unit Price</th>
            <td class="value">{{ number_format($record->price,2) }}</td>
        </tr>
        <tr>
            <th class="label">Quantity (mt)</th>
            <td class="value">{{ number_format($record->quantity,2) }}</td>
        </tr>
        <tr>
            <th class="label">Percentage</th>
            <td class="value">{{ $record->percentage }}%</td>
        </tr>
        <tr>
            <th class="label">Part</th>
            <td class="value">{{ $record->part }}</td>
        </tr>
    </table>

    <!-- Extra Information -->
    <h2>Extra Information</h2>
    @if(is_array($record->extra))
        @foreach($record->extra as $key => $value)
            <div><span class="label">{{ ucfirst($key) }}:</span>
                <span class="value">
                @if(is_array($value))
                        {{ implode(', ', $value) }}
                    @else
                        {{ $value }}
                    @endif
            </span>
            </div>
        @endforeach
    @else
        <div><span class="label">Extra:</span><span class="value">{{ $record->extra }}</span></div>
    @endif

    <!-- Details Section -->
    <h2>Details</h2>
    <table class="table">
        <tr>
            <th class="label">Details</th>
            <td class="value">{{ $record->details['notes'] ?? 'N/A' }}</td>
        </tr>
        <tr>
            <th class="label">Created on</th>
            <td class="value">{{ optional($record->created_at)->format('M d, Y') }}</td>
        </tr>
        <tr>
            <th class="label">Updated on</th>
            <td class="value">{{ optional($record->updated_at)->format('M d, Y') }}</td>
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

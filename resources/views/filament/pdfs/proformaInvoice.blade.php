<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BMS Pro forma Invoice Details</title>
    <style>
        body {
            font-family: DejaVu Sans, 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 1px;
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

        h3 {
            margin: 1.5em 0 0.5em;
            color: #2980b9;
            font-size: 16px;
            padding-bottom: .5em;
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
            font-family: monospace;
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
            border: 1px solid #ddd;
            border-radius: 4px;
            overflow: hidden;
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 40px;
            page-break-inside: auto;
        }

        .table th, .table td {
            border: none;
            border-bottom: 1px solid #eee; /* light divider */
            padding: 12px;
            text-align: left;
        }

        .table tr:nth-child(even) {
            background-color: #fbfbfb; /* zebra stripe */
        }

        .table tr:last-child th,
        .table tr:last-child td {
            border-bottom: none;
        }

        .table tr {
            page-break-inside: avoid;
            page-break-after: auto;
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

        .table td pre.value {
            margin: 0;
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
            margin-top: 20px;
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
            @if($record->reference_number)
                <div>Reference #: {{ $record->reference_number }}</div>
            @endif

            @if($record->proforma_number)
                <div>Proforma Number: {{ $record->proforma_number }}</div>
            @endif

            @if($record->proforma_date)
                <div>Proforma Date: {{ $record->proforma_date->format('M d, Y') }}</div>
            @endif

            @if($record->status)
                <div>Status: {{ ucfirst($record->status) }}</div>
            @endif
        </div>
    </div>

    <!-- General Information -->
    <h3>General Information</h3>
    <table class="table">
        @if($record->contract_number)
            <tr>
                <th class="label">Contract Number</th>
                <td class="value">{{ $record->contract_number }}</td>
            </tr>
        @endif

        @if(optional($record->category)->name)
            <tr>
                <th class="label">Category</th>
                <td class="value">{{ $record->category->name }}</td>
            </tr>
        @endif

        @if(optional($record->product)->name)
            <tr>
                <th class="label">Product</th>
                <td class="value">{{ $record->product->name }}</td>
            </tr>
        @endif

        @if(optional($record->grade)->name)
            <tr>
                <th class="label">Grade</th>
                <td class="value">{{ $record->grade->name }}</td>
            </tr>
        @endif
    </table>

    <!-- Buyer and Supplier Information -->
    <h3>Buyer and Supplier Information</h3>
    <table class="table">
        @if(optional($record->buyer)->name)
            <tr>
                <th class="label">Buyer</th>
                <td class="value">{{ $record->buyer->name }}</td>
            </tr>
        @endif

        @if(optional($record->supplier)->name)
            <tr>
                <th class="label">Supplier</th>
                <td class="value">{{ $record->supplier->name }}</td>
            </tr>
        @endif
    </table>

    <!-- Pricing and Quantity Information -->
    <h3>Pricing and Quantity</h3>
    <table class="table">
        @if($record->price)
            <tr>
                <th class="label">Unit Price</th>
                <td class="value">{{ number_format($record->price, 2) }}</td>
            </tr>
        @endif

        @if($record->quantity)
            <tr>
                <th class="label">Quantity (mt)</th>
                <td class="value">{{ number_format($record->quantity, 2) }}</td>
            </tr>
        @endif

        @if($record->percentage)
            <tr>
                <th class="label">Percentage</th>
                <td class="value">{{ $record->percentage }}%</td>
            </tr>
        @endif

        @if($record->part)
            <tr>
                <th class="label">Part</th>
                <td class="value">{{ $record->part }}</td>
            </tr>
        @endif
    </table>

    <!-- Extra Information -->
    <h3>Extra Information</h3>
    @if(is_array($record->extra))
        @foreach($record->extra as $key => $value)
            @if($value)
                <div><span class="label">{{ ucfirst($key) }}:</span> <span class="value">
                    @if(is_array($value))
                            {{ implode(', ', $value) }}
                        @else
                            {{ $value }}
                        @endif
                </span></div>
            @endif
        @endforeach
    @endif

    <!-- Details Section -->
    <h3>Details</h3>
    <table class="table">
        @if($record->details['notes'])
            <tr>
                <th class="label">Details</th>
                <td class="value">{{ $record->details['notes'] }}</td>
            </tr>
        @endif

        @if($record->created_at)
            <tr>
                <th class="label">Created on</th>
                <td class="value">{{ $record->created_at->format('M d, Y') }}</td>
            </tr>
        @endif

        @if($record->updated_at)
            <tr>
                <th class="label">Updated on</th>
                <td class="value">{{ $record->updated_at->format('M d, Y') }}</td>
            </tr>
        @endif
    </table>

    <div class="footer">
        Created on {{ optional($record->created_at)->format('M d, Y') }} by {{ optional($record->user)->fullName }}
        <br>
        BMS print preview service
    </div>
</div>
</body>
</html>

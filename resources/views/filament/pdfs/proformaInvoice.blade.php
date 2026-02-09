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
            <h1>Pro forma Invoice Details</h1>
            @if($record->reference_number ?? null)
                <div>Reference #: {{ $record->reference_number }}</div>
            @endif
            @if($record->proforma_number ?? null)
                <div>Proforma Number: {{ $record->proforma_number }}</div>
            @endif
            @if($record->proforma_date ?? null)
                <div>Proforma Date: {{ $record->proforma_date->format('M d, Y') }}</div>
            @endif
            @if($record->status ?? null)
                <div>Status: {{ ucfirst($record->status) }}</div>
            @endif
        </div>
    </div>

    <h3>General Information</h3>
    <table class="table">
        @if($record->contract_number ?? null)
            <tr>
                <th>Contract Number</th>
                <td>{{ $record->contract_number }}</td>
            </tr>
        @endif
        @if($record->category->name ?? null)
            <tr>
                <th>Category</th>
                <td>{{ $record->category->name }}</td>
            </tr>
        @endif
        @if($record->product->name ?? null)
            <tr>
                <th>Product</th>
                <td>{{ $record->product->name }}</td>
            </tr>
        @endif
        @if($record->grade->name ?? null)
            <tr>
                <th>Grade</th>
                <td>{{ $record->grade->name }}</td>
            </tr>
        @endif
    </table>

    <h3>Buyer and Supplier Information</h3>
    <table class="table">
        @if($record->buyer->name ?? null)
            <tr>
                <th>Buyer</th>
                <td>{{ $record->buyer->name }}</td>
            </tr>
        @endif
        @if($record->supplier->name ?? null)
            <tr>
                <th>Supplier</th>
                <td>{{ $record->supplier->name }}</td>
            </tr>
        @endif
    </table>

    <h3>Pricing and Quantity</h3>
    <table class="table">
        @if($record->price ?? null)
            <tr>
                <th>Unit Price</th>
                <td>{{ number_format($record->price, 2) }}</td>
            </tr>
        @endif
        @if($record->quantity ?? null)
            <tr>
                <th>Quantity (mt)</th>
                <td>{{ number_format($record->quantity, 2) }}</td>
            </tr>
        @endif
        @if($record->percentage ?? null)
            <tr>
                <th>Percentage</th>
                <td>{{ $record->percentage }}%</td>
            </tr>
        @endif
        @if($record->part ?? null)
            <tr>
                <th>Part</th>
                <td>{{ $record->part }}</td>
            </tr>
        @endif
    </table>

    @if(is_array($record->extra ?? null) && !empty($record->extra))
        <h3>Extra Information</h3>
        <table class="table">
            @foreach($record->extra as $key => $value)
                @if($value ?? null)
                    <tr>
                        <th>{{ ucfirst(str_replace('_', ' ', $key)) }}</th>
                        <td>
                            @if(is_array($value))
                                {{ implode(', ', $value) }}
                            @else
                                {{ $value }}
                            @endif
                        </td>
                    </tr>
                @endif
            @endforeach
        </table>
    @endif

    <h3>Details</h3>
    <table class="table">
        @if($record->details['notes'] ?? null)
            <tr>
                <th>Notes</th>
                <td>{{ $record->details['notes'] }}</td>
            </tr>
        @endif
        @if($record->created_at ?? null)
            <tr>
                <th>Created on</th>
                <td>{{ $record->created_at->format('M d, Y') }}</td>
            </tr>
        @endif
        @if($record->updated_at ?? null)
            <tr>
                <th>Updated on</th>
                <td>{{ $record->updated_at->format('M d, Y') }}</td>
            </tr>
        @endif
    </table>

    <div class="footer">
        Created on {{ $record->created_at ? $record->created_at->format('M d, Y') : 'Undefined' }}
        by {{ $record->user->fullName ?? 'Undefined' }}
        <br>
        BMS print preview service
    </div>
</div>
</body>
</html>

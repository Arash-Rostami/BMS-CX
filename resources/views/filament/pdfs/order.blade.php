<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BMS Order Details</title>
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
            <h1>Order Details</h1>
            @if($record->reference_number ?? null)
                <div>Reference #: {{ $record->reference_number }}</div>
            @endif
            @if($record->proforma_number ?? null)
                <div>Proforma Number: {{ $record->proforma_number }}</div>
            @endif
            @if($record->proforma_date ?? null)
                <div>Proforma Date: {{ $record->proforma_date->format('M d, Y') }}</div>
            @endif
            @if($record->order_status ?? null)
                <div>Status: {{ ucfirst($record->order_status) }}</div>
            @endif
            @if($record->purchaseStatus->bareTitle ?? null)
                <div>Shipment: {{ $record->purchaseStatus->bareTitle }}</div>
            @endif
        </div>
    </div>

    <h3>General Information</h3>
    <table class="table">
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
        @if($record->party->buyer->name ?? null)
            <tr>
                <th>Buyer</th>
                <td>{{ $record->party->buyer->name }}</td>
            </tr>
        @endif
        @if($record->party->supplier->name ?? null)
            <tr>
                <th>Supplier</th>
                <td>{{ $record->party->supplier->name }}</td>
            </tr>
        @endif
        @if($record->part ?? null)
            <tr>
                <th>Part</th>
                <td>{{ $record->part }}</td>
            </tr>
        @endif
    </table>

    <h3>Order Details</h3>
    <table class="table">
        @if($record->orderDetail->buying_quantity ?? null)
            <tr>
                <th>Initial Quantity (mt)</th>
                <td>{{ $record->orderDetail->buying_quantity }}</td>
            </tr>
        @endif
        @if($record->orderDetail->provisional_quantity ?? null)
            <tr>
                <th>Provisional Quantity (mt)</th>
                <td>{{ $record->orderDetail->provisional_quantity }}</td>
            </tr>
        @endif
        @if($record->orderDetail->final_quantity ?? null)
            <tr>
                <th>Final Quantity (mt)</th>
                <td>{{ $record->orderDetail->final_quantity }}</td>
            </tr>
        @endif
        @if($record->orderDetail->buying_price ?? null)
            <tr>
                <th>Initial Unit Price</th>
                <td>{{ $record->orderDetail->buying_price }}</td>
            </tr>
        @endif
        @if($record->orderDetail->provisional_price ?? null)
            <tr>
                <th>Provisional Unit Price</th>
                <td>{{ $record->orderDetail->provisional_price }}</td>
            </tr>
        @endif
        @if($record->orderDetail->final_price ?? null)
            <tr>
                <th>Final Unit Price</th>
                <td>{{ $record->orderDetail->final_price }}</td>
            </tr>
        @endif
        @if($record->orderDetail->currency ?? null)
            <tr>
                <th>Currency</th>
                <td>{{ $record->orderDetail->currency }}</td>
            </tr>
        @endif
    </table>

    <h3>Payment</h3>
    <table class="table">
        @if($record->orderDetail->initial_payment ?? null)
            <tr>
                <th>Pre-payment</th>
                <td>{{ $record->orderDetail->initial_payment }}</td>
            </tr>
        @endif
        @if($record->orderDetail->provisional_total ?? null)
            <tr>
                <th>Provisional Payment</th>
                <td>{{ $record->orderDetail->provisional_total }}</td>
            </tr>
        @endif
        @if($record->orderDetail->final_total ?? null)
            <tr>
                <th>Final Payment</th>
                <td>{{ $record->orderDetail->final_total }}</td>
            </tr>
        @endif
    </table>

    <h3>Logistics</h3>
    <table class="table">
        @if($record->logistic->deliveryTerm->name ?? null)
            <tr>
                <th>Delivery Term</th>
                <td>{{ $record->logistic->deliveryTerm->name }}</td>
            </tr>
        @endif
        @if($record->logistic->packaging->name ?? null)
            <tr>
                <th>Packaging</th>
                <td>{{ $record->logistic->packaging->name }}</td>
            </tr>
        @endif
        @if($record->logistic->shippingLine->name ?? null)
            <tr>
                <th>Shipping Line</th>
                <td>{{ $record->logistic->shippingLine->name }}</td>
            </tr>
        @endif
        @if($record->logistic->portOfDelivery->name ?? null)
            <tr>
                <th>Port of Delivery</th>
                <td>{{ $record->logistic->portOfDelivery->name }}</td>
            </tr>
        @endif
        @if($record->logistic->change_of_destination ?? null)
            <tr>
                <th>Change of Destination</th>
                <td>{{ $record->logistic->change_of_destination ? 'Yes' : 'No' }}</td>
            </tr>
        @endif
        @if($record->logistic->number_of_containers ?? null)
            <tr>
                <th>No. of Containers</th>
                <td>{{ $record->logistic->number_of_containers }}</td>
            </tr>
        @endif
        @if($record->logistic->terminal_handling_charges ?? null)
            <tr>
                <th>Terminal Handling Charges (THC)</th>
                <td>{{ $record->logistic->terminal_handling_charges }}</td>
            </tr>
        @endif
        @if($record->logistic->loading_startline ?? null)
            <tr>
                <th>Loading Start Date</th>
                <td>{{ $record->logistic->loading_startline->format('M d, Y') }}</td>
            </tr>
        @endif
        @if($record->logistic->loading_deadline ?? null)
            <tr>
                <th>Loading Deadline</th>
                <td>{{ $record->logistic->loading_deadline->format('M d, Y') }}</td>
            </tr>
        @endif
        @if($record->logistic->etd ?? null)
            <tr>
                <th>ETD</th>
                <td>{{ $record->logistic->etd->format('M d, Y') }}</td>
            </tr>
        @endif
        @if($record->logistic->eta ?? null)
            <tr>
                <th>ETA</th>
                <td>{{ $record->logistic->eta->format('M d, Y') }}</td>
            </tr>
        @endif
        @if($record->logistic->free_time_POD ?? null)
            <tr>
                <th>Free Time (POD)</th>
                <td>{{ $record->logistic->free_time_POD }}</td>
            </tr>
        @endif
        @if($record->logistic->FCL ?? null)
            <tr>
                <th>FCL</th>
                <td>{{ $record->logistic->FCL }}</td>
            </tr>
        @endif
        @if($record->logistic->full_container_load_type ?? null)
            <tr>
                <th>FCL Type</th>
                <td>{{ $record->logistic->full_container_load_type }}</td>
            </tr>
        @endif
        @if($record->logistic->ocean_freight ?? null)
            <tr>
                <th>Ocean Freight</th>
                <td>{{ $record->logistic->ocean_freight }}</td>
            </tr>
        @endif
        @if($record->logistic->gross_weight ?? null)
            <tr>
                <th>Gross Weight</th>
                <td>{{ $record->logistic->gross_weight }}</td>
            </tr>
        @endif
        @if($record->logistic->net_weight ?? null)
            <tr>
                <th>Net Weight</th>
                <td>{{ $record->logistic->net_weight }}</td>
            </tr>
        @endif
    </table>

    <h3>BL and Declaration</h3>
    <table class="table">
        @if($record->logistic->booking_number ?? null)
            <tr>
                <th>Booking Number</th>
                <td>{{ $record->logistic->booking_number }}</td>
            </tr>
        @endif
        @if($record->doc->voyage_number ?? null)
            <tr>
                <th>Voyage Number</th>
                <td>{{ $record->doc->voyage_number }}</td>
            </tr>
        @endif
        @if($record->doc->extra['voyage_number_second_leg'] ?? null)
            <tr>
                <th>Voyage Number (Second Leg)</th>
                <td>{{ $record->doc->extra['voyage_number_second_leg'] }}</td>
            </tr>
        @endif
        @if($record->doc->declaration_number ?? null)
            <tr>
                <th>Declaration Number</th>
                <td>{{ $record->doc->declaration_number }}</td>
            </tr>
        @endif
        @if($record->doc->declaration_date ?? null)
            <tr>
                <th>Declaration Date</th>
                <td>{{ $record->doc->declaration_date->format('M d, Y') }}</td>
            </tr>
        @endif
        @if($record->doc->BL_number ?? null)
            <tr>
                <th>BL Number</th>
                <td>{{ $record->doc->BL_number }}</td>
            </tr>
        @endif
        @if($record->doc->BL_date ?? null)
            <tr>
                <th>BL Date</th>
                <td>{{ $record->doc->BL_date->format('M d, Y') }}</td>
            </tr>
        @endif
        @if($record->doc->extra['BL_number_second_leg'] ?? null)
            <tr>
                <th>BL Number (Second Leg)</th>
                <td>{{ $record->doc->extra['BL_number_second_leg'] }}</td>
            </tr>
        @endif
        @if($record->doc->extra['BL_date_second_leg'] ?? null)
            <tr>
                <th>BL Date (Second Leg)</th>
                <td>{{ \Carbon\Carbon::parse($record->doc->extra['BL_date_second_leg'])->format('M d, Y') }}</td>
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

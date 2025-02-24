<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BMS Order Details</title>
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

        h2 {
            color: #34495e;
            font-size: 18px;
            margin: 40px 0 20px;
            border-bottom: 2px solid #ddd;
            padding-bottom: 10px;
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
            @if($record->reference_number)
                <div>Reference #: {{ $record->reference_number }}</div>
            @endif

            @if($record->proforma_number)
                <div>Proforma Number: {{ $record->proforma_number }}</div>
            @endif

            @if($record->proforma_date)
                <div>Proforma Date: {{ $record->proforma_date->format('M d, Y') }}</div>
            @endif

            @if($record->order_status)
                <div>Status: {{ ucfirst($record->order_status) }}</div>
            @endif

            @if($record->purchaseStatus)
                <div>Shipment: {{ $record->purchaseStatus->bareTitle }}</div>
            @endif
        </div>
    </div>

    <!-- General Information -->
    <h2>General Information</h2>
    <table class="table">
        @if(optional($record->category)->name)
            <tr>
                <th>Category</th>
                <td>{{ $record->category->name }}</td>
            </tr>
        @endif

        @if(optional($record->product)->name)
            <tr>
                <th>Product</th>
                <td>{{ $record->product->name }}</td>
            </tr>
        @endif

        @if(optional($record->grade)->name)
            <tr>
                <th>Grade</th>
                <td>{{ $record->grade->name }}</td>
            </tr>
        @endif

        @if(optional($record->party->buyer)->name)
            <tr>
                <th>Buyer</th>
                <td>{{ $record->party->buyer->name }}</td>
            </tr>
        @endif

        @if(optional($record->party->supplier)->name)
            <tr>
                <th>Supplier</th>
                <td>{{ $record->party->supplier->name }}</td>
            </tr>
        @endif

        @if($record->part)
            <tr>
                <th>Part</th>
                <td>{{ $record->part }}</td>
            </tr>
        @endif
    </table>

    <!-- Order Additional Details -->
    <h2>Order Details</h2>
    <table class="table">
        @if($record->orderDetail->buying_quantity)
            <tr>
                <th>Initial Quantity (mt)</th>
                <td>{{ $record->orderDetail->buying_quantity }}</td>
            </tr>
        @endif

        @if($record->orderDetail->provisional_quantity)
            <tr>
                <th>Provisional Quantity (mt)</th>
                <td>{{ $record->orderDetail->provisional_quantity }}</td>
            </tr>
        @endif

        @if($record->orderDetail->final_quantity)
            <tr>
                <th>Final Quantity (mt)</th>
                <td>{{ $record->orderDetail->final_quantity }}</td>
            </tr>
        @endif

        @if($record->orderDetail->buying_price)
            <tr>
                <th>Initial Unit Price</th>
                <td>{{ $record->orderDetail->buying_price }}</td>
            </tr>
        @endif

        @if($record->orderDetail->provisional_price)
            <tr>
                <th>Provisional Unit Price</th>
                <td>{{ $record->orderDetail->provisional_price }}</td>
            </tr>
        @endif

        @if($record->orderDetail->final_price)
            <tr>
                <th>Final Unit Price</th>
                <td>{{ $record->orderDetail->final_price }}</td>
            </tr>
        @endif

        @if($record->orderDetail->currency)
            <tr>
                <th>Currency</th>
                <td>{{ $record->orderDetail->currency }}</td>
            </tr>
        @endif
    </table>

    <!-- Payment Details Section -->
    <h2>Payment</h2>
    <table class="table">
        @if($record->orderDetail->initial_payment)
            <tr>
                <th>Pre-payment</th>
                <td>{{ $record->orderDetail->initial_payment }}</td>
            </tr>
        @endif

        @if($record->orderDetail->provisional_total)
            <tr>
                <th>Provisional Payment</th>
                <td>{{ $record->orderDetail->provisional_total }}</td>
            </tr>
        @endif

        @if($record->orderDetail->final_total)
            <tr>
                <th>Final Payment</th>
                <td>{{ $record->orderDetail->final_total}}</td>
            </tr>
        @endif
    </table>


    <!-- Logistics Details Section -->
    <h2>Logistics</h2>
    <table class="table">
        @if(optional($record->logistic->deliveryTerm)?->name)
            <tr>
                <th>Delivery Term</th>
                <td>{{ $record->logistic->deliveryTerm->name }}</td>
            </tr>
        @endif

        @if(optional($record->logistic->packaging)?->name)
            <tr>
                <th>Packaging</th>
                <td>{{ $record->logistic->packaging->name }}</td>
            </tr>
        @endif

        @if(optional($record->logistic->shippingLine)?->name)
            <tr>
                <th>Shipping Line</th>
                <td>{{ $record->logistic->shippingLine->name }}</td>
            </tr>
        @endif

        @if(optional($record->logistic->portOfDelivery)?->name)
            <tr>
                <th>Port of Delivery</th>
                <td>{{ $record->logistic->portOfDelivery->name }}</td>
            </tr>
        @endif

        @if($record->logistic->change_of_destination)
            <tr>
                <th>Change of Destination</th>
                <td>{{ $record->logistic->change_of_destination ? 'Yes' : 'No' }}</td>
            </tr>
        @endif

        @if($record->logistic->number_of_containers)
            <tr>
                <th>No. of Containers</th>
                <td>{{ $record->logistic->number_of_containers }}</td>
            </tr>
        @endif

        @if($record->logistic->terminal_handling_charges)
            <tr>
                <th>Terminal Handling Charges (THC)</th>
                <td>{{ $record->logistic->terminal_handling_charges }}</td>
            </tr>
        @endif

        @if($record->logistic?->loading_startline)
            <tr>
                <th>Loading Start Date</th>
                <td>{{ $record->logistic->loading_startline->format('M d, Y') }}</td>
            </tr>
        @endif

        @if($record->logistic?->loading_deadline)
            <tr>
                <th>Loading Deadline</th>
                <td>{{ $record->logistic->loading_deadline->format('M d, Y') }}</td>
            </tr>
        @endif

        @if($record->logistic?->etd)
            <tr>
                <th>ETD</th>
                <td>{{ $record->logistic->etd->format('M d, Y') }}</td>
            </tr>
        @endif

        @if($record->logistic?->eta)
            <tr>
                <th>ETA</th>
                <td>{{ $record->logistic->eta->format('M d, Y') }}</td>
            </tr>
        @endif

        @if($record->logistic->free_time_POD)
            <tr>
                <th>Free Time (POD)</th>
                <td>{{ $record->logistic->free_time_POD }}</td>
            </tr>
        @endif
        @if($record->logistic->FCL)
            <tr>
                <th>FCL</th>
                <td>{{ $record->logistic->FCL }}</td>
            </tr>
        @endif

        @if(optional($record->logistic)?->full_container_load_type)
            <tr>
                <th>FCL Type</th>
                <td>{{ $record->logistic->full_container_load_type }}</td>
            </tr>
        @endif

        @if($record->logistic->ocean_freight)
            <tr>
                <th>Ocean Freight</th>
                <td>{{ $record->logistic->ocean_freight }}</td>
            </tr>
        @endif

        @if($record->logistic->gross_weight)
            <tr>
                <th>Gross Weight</th>
                <td>{{ $record->logistic->gross_weight }}</td>
            </tr>
        @endif

        @if($record->logistic->net_weight)
            <tr>
                <th>Net Weight</th>
                <td>{{ $record->logistic->net_weight }}</td>
            </tr>
        @endif

    </table>


    <!-- BL and Declaration Details Section -->
    <h2>BL and Declaration</h2>
    <table class="table">
        @if($record->logistic->booking_number)
            <tr>
                <th>Booking Number</th>
                <td>{{ $record->logistic->booking_number }}</td>
            </tr>
        @endif
        @if(optional($record->doc)?->voyage_number)
            <tr>
                <th>Voyage Number</th>
                <td>{{ $record->doc->voyage_number }}</td>
            </tr>
        @endif

        @if(optional($record->doc)->extra['voyage_number_second_leg'])
            <tr>
                <th>Voyage Number (Second Leg)</th>
                <td>{{ $record->doc->extra['voyage_number_second_leg'] }}</td>
            </tr>
        @endif

        @if(optional($record->doc)?->declaration_number)
            <tr>
                <th>Declaration Number</th>
                <td>{{ $record->doc->declaration_number }}</td>
            </tr>
        @endif

        @if(optional($record->doc)?->declaration_date)
            <tr>
                <th>Declaration Date</th>
                <td>{{ $record->doc->declaration_date->format('M d, Y') }}</td>
            </tr>
        @endif

        @if(optional($record->doc)?->BL_number)
            <tr>
                <th>BL Number</th>
                <td>{{ $record->doc->BL_number }}</td>
            </tr>
        @endif

        @if(optional($record->doc)?->BL_date)
            <tr>
                <th>BL Date</th>
                <td>{{ $record->doc->BL_date->format('M d, Y') }}</td>
            </tr>
        @endif

        @if(optional($record->doc)->extra['BL_number_second_leg'])
            <tr>
                <th>BL Number (Second Leg)</th>
                <td>{{ $record->doc->extra['BL_number_second_leg'] }}</td>
            </tr>
        @endif

        @if(optional($record->doc)->extra['BL_date_second_leg'])
            <tr>
                <th>BL Date (Second Leg)</th>
                <td>{{ \Carbon\Carbon::parse($record->doc->extra['BL_date_second_leg'])->format('M d, Y') }}</td>
            </tr>
        @endif
    </table>


    <!-- Footer -->
    <div class="footer">
        Created on {{ optional($record->created_at)->format('M d, Y') }} by {{ optional($record->user)->fullName }}
        <br>
        BMS print preview service
    </div>
</div>
</body>
</html>

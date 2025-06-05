<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>PI & Orders Summary</title>
    <style>
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

        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #333;
            margin: 0;
            padding: 0;
        }

        h3 {
            margin: 1em 0 .5em;
            padding-bottom: .2em;
            padding-top: .5em;
            color: #2980b9;
        }

        .grid {
            display: table;
            width: 100%;
            margin-bottom: 1em;
            table-layout: fixed;
        }

        .grid .cell {
            display: table-cell;
            vertical-align: top;
            padding: .25em .5em;
            border: 1px solid #ccc;
        }

        .grid .cell.header {
            background: #f0f0f0;
            font-weight: bold;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1em;
        }

        th, td {
            border: 1px solid #ccc;
            padding: .3em .5em;
            vertical-align: top;
        }

        thead th {
            background: #e0e0e0;
            font-size: 12px;
        }

        tfoot th {
            background: #f9f9f9;
            font-weight: bold;
        }

        .text-right {
            text-align: right;
        }
    </style>
</head>
<body>
<div class="header">
    <div class="logo">BMS</div>
    <div class="details">
        <div>Contract No: {{ optional($proformaInvoice)->contract_number ?? 'N/A' }}</div>
        <div>Printed by {{ optional(auth()->user())->full_name ?? 'N/A' }} on {{ now()->format('M d, Y') }}</div>
    </div>
</div>

{{-- 1. Header info --}}
<h3>PI Details</h3>
<div class="grid">
    <div class="cell header">Reference No.</div>
    <div class="cell">{{ optional($proformaInvoice)->reference_number ?? 'N/A' }}</div>

    <div class="cell header">Proforma No.</div>
    <div class="cell">{{ optional($proformaInvoice)->proforma_number ?? 'N/A' }}</div>

    <div class="cell header">Proforma Date</div>
    <div class="cell">{{ optional(optional($proformaInvoice)->proforma_date)->format('d M, Y') ?? 'N/A' }}</div>

    <div class="cell header">Buyer</div>
    <div class="cell">{{ optional(optional($proformaInvoice)->buyer)->name ?? 'N/A' }}</div>

    <div class="cell header">Supplier</div>
    <div class="cell">{{ optional(optional($proformaInvoice)->supplier)->name ?? 'N/A' }}</div>
</div>

<div class="grid">
    <div class="cell header">Advance ({{ optional($proformaInvoice)->percentage ?? 0 }}%)</div>
    <div class="cell text-right">
        {{ number_format(
             ((optional($proformaInvoice)->percentage ?? 0) * ((optional($proformaInvoice)->quantity ?? 0) * (optional($proformaInvoice)->price ?? 0))) / 100,
             2
           ) }}
    </div>

    <div class="cell header">Quantity</div>
    <div class="cell text-right">{{ number_format(optional($proformaInvoice)->quantity ?? 0, 0) }} mt</div>

    <div class="cell header">Price</div>
    <div class="cell text-right">{{ number_format(optional($proformaInvoice)->price ?? 0, 2) }}</div>

    <div class="cell header">Total</div>
    <div class="cell text-right">
        {{ number_format(((optional($proformaInvoice)->quantity ?? 0) * (optional($proformaInvoice)->price ?? 0)), 2) }}
    </div>
</div>

{{-- 2. Order Summary table --}}
<h3>Order Summary</h3>
<table>
    <thead>
    <tr>
        <th>Part (Ref)</th>
        <th>BL Date</th>
        <th>Prices (P | F)</th>
        <th class="text-right">Quantity</th>
        <th class="text-right">Advance</th>
        <th class="text-right">Total</th>
    </tr>
    </thead>
    @if(isset($orderSummary['rows']) && is_array($orderSummary['rows']))
        <tbody>
        @foreach($orderSummary['rows'] as $row)
            <tr>
                <td>{{ $row['part'] ?? 'N/A' }} ({{ $row['reference_number'] ?? 'N/A' }})</td>
                <td>{{ $row['bl_date'] ?? 'N/A' }}</td>
                <td>
                    {{ $row['currency'] ?? '' }}
                    @php $provisionalPrice = $row['provisional_price'] ?? null; @endphp
                    @if (isset($provisionalPrice) && is_numeric($provisionalPrice))
                        {{ number_format($provisionalPrice, 2) }}
                    @else
                        {{ $provisionalPrice ?? 'N/A' }}
                    @endif
                    │
                    @php $finalPrice = $row['final_price'] ?? null; @endphp
                    @if (isset($finalPrice) && is_numeric($finalPrice))
                        {{ number_format($finalPrice, 2) }}
                    @else
                        {{ $finalPrice ?? 'N/A' }}
                    @endif
                </td>
                <td class="text-right">
                    @php $quantity = $row['quantity'] ?? null; @endphp
                    @if (isset($quantity) && is_numeric($quantity))
                        {{ number_format($quantity, 2) }}
                    @else
                        {{ $quantity ?? 'N/A' }}
                    @endif
                </td>
                <td class="text-right">
                    @php $initialPayment = $row['initial_payment'] ?? null; @endphp
                    @if (isset($initialPayment) && is_numeric($initialPayment))
                        {{ number_format($initialPayment, 2) }}
                    @else
                        {{ $initialPayment ?? 'N/A' }}
                    @endif
                </td>
                <td class="text-right">
                    @php $total = $row['total'] ?? null; @endphp
                    @if (isset($total) && is_numeric($total))
                        {{ number_format($total, 2) }}
                    @else
                        {{ $total ?? 'N/A' }}
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    @else
        <tbody>
        <tr>
            <td colspan="6">No order summary data available.</td>
        </tr>
        </tbody>
    @endif
    <tfoot>
    <tr>
        <th colspan="3">Sum</th>
        <th class="text-right">{{ number_format($orderSummary['totals']['quantity'] ?? 0, 2) }}</th>
        <th class="text-right">{{ number_format($orderSummary['totals']['initial_payment'] ?? 0, 2) }}</th>
        <th class="text-right">{{ number_format($orderSummary['totals']['sum'] ?? 0, 2) }}</th>
    </tr>
    </tfoot>
</table>

{{-- 3. Payments table --}}
<h3>Payment Details</h3>
<table>
    <thead>
    <tr>
        <th>Reference No.</th>
        <th>SWIFT</th>
        <th>Payer</th>
        <th>Deadline</th>
        <th>Value Date</th>
        <th class="text-right">Amount</th>
        <th class="text-right">Balance</th>
    </tr>
    </thead>
    @if(isset($paymentSummary['rows']) && is_array($paymentSummary['rows']))
        <tbody>
        @foreach($paymentSummary['rows'] as $row)
            <tr>
                <td>{{ $row['reference_number'] ?? 'N/A' }}</td>
                <td>{{ $row['swift'] ?? 'N/A' }}</td>
                <td>{{ $row['payer'] ?? 'N/A' }}</td>
                <td>{{ $row['deadline'] ?? 'N/A' }}</td>
                <td>{{ $row['value_date'] ?? 'N/A' }}</td>
                <td class="text-right">
                    @php $amount = $row['amount'] ?? null; @endphp
                    @if (isset($amount) && is_numeric($amount))
                        {{ number_format($amount, 2) }}
                    @else
                        {{ $amount ?? 'N/A' }}
                    @endif
                </td>
                <td class="text-right">
                    @php $balance = $row['balance'] ?? null; @endphp
                    @if (isset($balance) && is_numeric($balance))
                        {{ number_format($balance, 2) }}
                    @else
                        {{ $balance ?? 'N/A' }}
                    @endif
                </td>
            </tr>
        @endforeach
        </tbody>
    @else
        <tbody>
        <tr>
            <td colspan="7">No payment details available.</td>
        </tr>
        </tbody>
    @endif
    <tfoot>
    <tr>
        <th colspan="5">Sum</th>
        <th class="text-right">{{ number_format($paymentSummary['totals']['paid'] ?? 0, 2) }}</th>
        <th class="text-right">{{ number_format($paymentSummary['totals']['balance'] ?? 0, 2) }}</th>
    </tr>
    </tfoot>
</table>
</body>
</html>

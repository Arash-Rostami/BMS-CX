@php
    $record = $this->form->getRawState();
    $errors = cache('errors', []);


    function extractPaymentData(array $record): array {
        $orderDetail = $record['orderDetail'] ?? [];
        $extra = $orderDetail['extra'] ?? [];

        return [
            'initialAdvancePayment' => $extra['initialPayment'] ?? '± 0',
            'provisionalTotalPayment' => $extra['provisionalTotal'] ?? '± 0',
            'finalTotalPayment' => $extra['finalTotal'] ?? '± 0',
            'cumulativePayment' => $extra['payment'] ?? '± 0',
            'remainingPayment' => $extra['remaining'] ?? '± 0',
            'totalPayment' => $extra['total'] ?? '± 0',
            'currency' => $extra['currency'] ?? '',
            'payableQuantity' => $extra['payableQuantity'] ?? ''
        ];
    }

    $paymentData = extractPaymentData($record);
@endphp
@if(count($errors) > 0)
    <div title="Errors"
         class="max-w-md mx-auto mb-4 p-4 px-4 py-2 text-white shadow-lg rounded-lg bg-primary-600 cursor-help">
        <span class="text-center">✋🏻</span>
        <ul class="list-none text-sm">
            @foreach($errors as $error)
                <li> {{ $error['message'] }} </li>
            @endforeach
        </ul>
    </div>
@endif
<!-- Receipt Container with Perforated Style -->
<div
    class="max-w-md mx-auto p-6 bg-gray-100 dark:bg-gray-800 shadow-lg rounded-lg border-2 border-gray-300 dark:border-gray-700 font-mono print:border-none print:shadow-none border-dotted">
    <div class="w-full">
        <!-- First Table for Payment Details -->
        <table class="w-full text-sm">
            <tbody>
            <tr class="border-b border-gray-300 dark:border-gray-600">
                <td class="px-4 py-2 text-gray-600 dark:text-white">Initial Advance:</td>
                <td class="px-4 py-2 text-right font-semibold dark:text-white">{{ numberify($paymentData['initialAdvancePayment']) }}</td>
            </tr>
            <tr class="border-b border-gray-300 dark:border-gray-600">
                <td class="px-4 py-2 text-gray-600 dark:text-white">Provisional Payment:</td>
                <td class="px-4 py-2 text-right font-semibold dark:text-white">

                    {{ numberify($paymentData['provisionalTotalPayment']) }}

                </td>
            </tr>
            <tr class="border-b border-gray-300 dark:border-gray-600">
                <td class="px-4 py-2 text-gray-600 dark:text-white">Final Payment:</td>
                <td class="px-4 py-2 text-right font-semibold dark:text-white">

                    {{ numberify($paymentData['finalTotalPayment']) }}

                </td>
            </tr>
            </tbody>
        </table>

        <!-- Second Table for Cumulative, Remaining, and Total Payment -->
        <table class="w-full text-sm mt-4">
            <thead>
            <tr class="text-center bg-gray-200 dark:bg-gray-700 rounded">
                <th class="px-4 py-2 font-bold text-gray-600 dark:text-white">Cumulative</th>
                <th class="px-4 py-2 font-bold text-gray-600 dark:text-white">Remaining</th>
                <th class="px-4 py-2 font-bold text-gray-600 dark:text-white">Total</th>
            </tr>
            </thead>
            <tbody>
            <tr class="text-center">
                <td class="px-4 py-2 font-semibold text-emerald-800">{{ numberify($paymentData['cumulativePayment']) }}</td>
                <td class="px-4 py-2 font-semibold text-red-700">{{ numberify($paymentData['remainingPayment']) }}</td>
                <td class="px-4 py-2 font-semibold dark:text-white">{{ numberify($paymentData['totalPayment']) }}</td>
            </tr>
            </tbody>
        </table>
    </div>
    @if($paymentData['currency'] != '')
        <span
            class="font-mono text-xs text-right float-right scale-75">denominated in {{ $paymentData['currency'] }}</span>
    @endif

</div>
@if($paymentData['payableQuantity'] != '')
    <span
        class="font-mono text-xs text-center scale-50 p-2">available quantity w/o this record: {{ $paymentData['payableQuantity'] }} (mt)</span>
    <br>
@endif


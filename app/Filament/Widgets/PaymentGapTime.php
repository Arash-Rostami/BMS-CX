<?php

namespace App\Filament\Widgets;

use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class PaymentGapTime extends ChartWidget
{
    protected static ?string $heading = '⏱️ Payment Request Completion';

    protected int|string|array $columnSpan = '350px';

    protected function getType(): string
    {
        return 'bar';
    }

    private function getPaymentRequestData(): array
    {
        $query = "
        SELECT
            d.code,
            AVG(TIMESTAMPDIFF(DAY, pr.created_at, p.date)) as avg_time_to_payment,
            AVG(TIMESTAMPDIFF(DAY, pr.created_at, p.created_at)) as avg_time_to_recording
        FROM payments p
        INNER JOIN payment_payment_request ppr ON p.id = ppr.payment_id
        INNER JOIN payment_requests pr ON ppr.payment_request_id = pr.id
        INNER JOIN departments d ON pr.department_id = d.id
        WHERE pr.status = 'completed'
          AND pr.deleted_at IS NULL
          AND p.deleted_at IS NULL
          AND p.date IS NOT NULL
          AND pr.created_at IS NOT NULL
          AND p.created_at IS NOT NULL
        GROUP BY d.code
        ";
        $data = DB::select($query);

        $avgTimeToPayment = [];
        $avgTimeToRecording = [];
        $labels = [];

        foreach ($data as $row) {
            $avgTimeToPayment[] = $row->avg_time_to_payment;
            $avgTimeToRecording[] = $row->avg_time_to_recording;
            $labels[] = $row->code;
        }

        return [
            'avg_time_to_payment' => $avgTimeToPayment,
            'avg_time_to_recording' => $avgTimeToRecording,
            'labels' => $labels,
        ];
    }

    protected function getData(): array
    {
        $data = $this->getPaymentRequestData();

        return [
            'datasets' => [
                [
                    'label' => 'Avg. Time to Payment (Days)',
                    'data' => $data['avg_time_to_payment'],
                    'borderColor' => '#eda900',
                    'backgroundColor' => '#eda900',
                    'fill' => true,
                ],
                [
                    'label' => 'Avg. Time to Recording (Days)',
                    'data' => $data['avg_time_to_recording'],
                    'borderColor' => '#577BC1',
                    'backgroundColor' => '#577BC1',
                    'fill' => true,
                ],
            ],
            'labels' => $data['labels'],
        ];
    }

}

<?php

namespace App\Filament\Resources\Dashboard\LoanSummaryResource\Exports;


use Illuminate\Database\Eloquent\Collection;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class LoanSummaryExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize
{
    public function __construct(protected Collection $records) {}

    public function headings(): array
    {
        return [
            'Type',
            'Description',
            'Disbursement Date',
            'Disbursed Amount',
            'Applied Credit',
            'Settlement Date',
            'Settlement Amount',
            'Duration (Days)',
            'Accrued Interest',
            'Deducted (Principal)',
            'Counter Interest',
            'Avg Duration',
            'Avg Settlement',
            'Remaining Principal',
            'Settled',
        ];
    }

    public function array(): array
    {
        $rows = [];

        foreach ($this->records as $record) {
            $isReceipt = $record->entry_type === 'receipt';

            if ($isReceipt || blank($record->settlement_dates)) {
                $rows[] = [
                    $record->entry_type,
                    $record->description,
                    $record->disbursement_date?->format('Y-m-d'),
                    $record->total_disbursed_base,
                    $record->applied_credit_amount,
                    'N/A',
                    'N/A',
                    'N/A',
                    'N/A',
                    'N/A',
                    'N/A',
                    'N/A',
                    'N/A',
                    $record->remaining_principal,
                    $record->is_settled ? 'Yes' : 'No',
                ];
                continue;
            }

            $dates     = $this->splitLines($record->settlement_dates);
            $amounts   = $this->splitLines($record->settlement_amounts);
            $durations = $this->splitLines($record->duration_days);
            $interests = $this->splitLines($record->accrued_interests);
            $deducted  = $this->splitLines($record->deducted_from_principals);
            $counter   = $this->splitLines($record->counter_interests);
            $count     = max(count($dates), 1);

            for ($i = 0; $i < $count; $i++) {
                $rows[] = [
                    $i === 0 ? $record->entry_type          : '',
                    $i === 0 ? $record->description         : '',
                    $i === 0 ? $record->disbursement_date?->format('Y-m-d') : '',
                    $i === 0 ? $record->total_disbursed_base : '',
                    $i === 0 ? $record->applied_credit_amount : '',
                    $dates[$i]     ?? '',
                    $amounts[$i]   ?? '',
                    $durations[$i] ?? '',
                    $interests[$i] ?? '',
                    $deducted[$i]  ?? '',
                    $counter[$i]   ?? '',
                    $i === 0 ? $record->avg_duration_days    : '',
                    $i === 0 ? $record->avg_settlement_amount : '',
                    $i === 0 ? $record->remaining_principal  : '',
                    $i === 0 ? ($record->is_settled ? 'Yes' : 'No') : '',
                ];
            }
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    private function splitLines(?string $text): array
    {
        if (blank($text)) return [];
        return array_values(array_filter(
            preg_split('/\r\n|\r|\n|\|/', trim($text))
        ));
    }
}

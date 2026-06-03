<?php

namespace App\Filament\Resources\Dashboard\AccountStatementResource\Exports;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class AccountStatementExport implements FromArray, WithHeadings, WithStyles, ShouldAutoSize
{
    public function __construct(protected Collection $records) {}

    public function headings(): array
    {
        return [
            'Transaction Date',
            'Type',
            'Description',
            'Credit (Out)',
            'Debit (In)',
            'Applied Credit',
            'Interest (Accrued)',
            'Counter Interest',
            'Net Movement',
            'Running Balance',
        ];
    }

    public function array(): array
    {
        $rows = [];

        foreach ($this->records as $record) {
            $net = $record->event_type === 'settlement'
                ? (float) $record->debit - (float) $record->credit - (float) ($record->counter_interest ?? 0)
                : (float) $record->debit - (float) $record->credit + (float) ($record->accrued_interest ?? 0);

            $debit = $record->event_type === 'disbursement' ? $record->debit : 0;

            $date = $record->transaction_date instanceof Carbon
                ? $record->transaction_date->format('Y-m-d')
                : Carbon::parse($record->transaction_date)->format('Y-m-d');

            $rows[] = [
                $date,
                $record->event_type,
                $record->description,
                $debit,
                $record->credit,
                $record->applied_credit ?? 0,
                $record->accrued_interest ?? 0,
                $record->counter_interest ?? 0,
                $net,
                $record->running_balance ?? 0,
            ];
        }

        return $rows;
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }
}

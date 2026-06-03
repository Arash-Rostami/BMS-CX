<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LoanSummary extends Model
{
    public $timestamps = false;
    protected $table = 'loan_summaries';
    protected $primaryKey = 'id';

    protected $casts = [
        'disbursement_date' => 'date',
        'total_disbursed_base' => 'decimal:6',
        'applied_credit_amount' => 'decimal:6',
        'remaining_principal' => 'decimal:6',
        'avg_duration_days' => 'decimal:0',
        'avg_settlement_amount' => 'decimal:2',
        'is_settled' => 'boolean',
    ];

    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class);
    }

    public function accruedInterestsList(): array
    {
        return $this->splitLines($this->accrued_interests ?? null);
    }

    public function counterInterestsList(): array
    {
        return $this->splitLines($this->counter_interests ?? null);
    }

    public function deductedFromPrincipalsList(): array
    {
        return $this->splitLines($this->deducted_from_principals ?? null);
    }

    public function durationDaysList(): array
    {
        return $this->splitLines($this->duration_days ?? null);
    }

    public function entryTypeColor(): string
    {
        return match ($this->entry_type) {
            'disbursement' => 'danger',
            'receipt' => 'success',
            default => 'secondary',
        };
    }

    public function entryTypeLabel(): string
    {
        return match ($this->entry_type) {
            'disbursement' => 'Disbursement',
            'receipt' => 'Receipt',
            default => 'Unknown',
        };
    }

    public function entryTypeTooltip(): string
    {
        return match ($this->entry_type) {
            'disbursement' => 'Funds released — outgoing principal.',
            'receipt' => 'Incoming payment received.',
            default => '',
        };
    }

    public function ledgerEntry(): BelongsTo
    {
        return $this->belongsTo(LedgerEntry::class, 'id');
    }

    public function settledColor(): string
    {
        return $this->is_settled ? 'success' : 'danger';
    }

    public function settledLabel(): string
    {
        return $this->is_settled ? 'Yes' : 'No';
    }

    public function settlementAmountsList(): array
    {
        return $this->splitLines($this->settlement_amounts ?? null);
    }

    public function settlementDatesList(): array
    {
        return $this->splitLines($this->settlement_dates ?? null);
    }

    public function settlementPairs(): array
    {
        $dates = $this->settlementDatesList();
        $amounts = $this->settlementAmountsList();

        $count = min(count($dates), count($amounts));
        $pairs = [];

        for ($i = 0; $i < $count; $i++) {
            $pairs[] = [
                'date' => trim($dates[$i]),
                'amount' => trim($amounts[$i]),
            ];
        }

        return $pairs;
    }

    public function summaryHeadline(): string
    {
        return $this->description ?: '—';
    }

    protected function splitLines(?string $value): array
    {
        return array_values(array_filter(preg_split('/\r\n|\r|\n/', trim((string)$value)) ?: []));
    }
}

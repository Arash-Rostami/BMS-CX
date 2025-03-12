<?php

namespace App\Filament\Widgets;

use App\Models\PaymentRequest;
use App\Services\Repository\PaymentRequestRepository;
use Filament\Widgets\Concerns\InteractsWithPageFilters;
use Filament\Widgets\TableWidget as BaseWidget;
use Filament\Tables;
use Illuminate\Database\Eloquent\Builder;


class ListOfBeneficiaries extends BaseWidget
{

    use InteractsWithPageFilters;

    protected int|string|array $columnSpan = 1;

    protected static ?string $heading = '👤 Beneficiaries';

    protected static bool $isLazy = false;


    protected function getTableQuery(): Builder
    {
        $repository = new PaymentRequestRepository();

        $user = auth()->user();
        $departmentFilter = $user
            ? ((isUserSnrAccountant() || isUserManager() || isUserAdmin())
                ? ($this->filters['department'] ?? null)
                : ($user->info['department'] ?? null))
            : null;


        $repository
            ->filterByTimePeriod($this->filters['period'] ?? 'monthly')
            ->filterByAttributes([
                'department' => $departmentFilter,
                'currency' => $this->filters['currency'] ?? null,
                'payment_type' => $this->filters['payment_type'] ?? null,
                'status' => $this->filters['status'] ?? null,
            ]);

        return $repository->query;
    }


    protected function getTableColumns(): array
    {
        return [
            Tables\Columns\TextColumn::make('beneficiary_name')
                ->label('Beneficiary')
                ->formatStateUsing(function ($record) {
                    return $record->contractor?->name
                        ?? $record->supplier?->name
                        ?? $record->beneficiary?->name
                        ?? 'Unknown';
                })
                ->badge()
                ->tooltip(fn($record) => "Created: " . $record->created_at)
                ->color('primary')
                ->sortable()
                ->searchable(query: fn($query, $search) => PaymentRequest::searchBeneficiaries($query, $search)),

            Tables\Columns\TextColumn::make('requested_amount')
                ->label('Requested Amount')
                ->formatStateUsing(function ($state, PaymentRequest $record) {
                    return $record->currency . ' ' . number_format($state, 2);
                })
                // ->summarize(Sum::make()->label('Total'))
                ->searchable()
                ->sortable(),

            Tables\Columns\TextColumn::make('deadline')
                ->label('Deadline')
                ->date()
                ->badge()
                ->color('secondary')
                ->sortable(),
        ];
    }

    protected function getDefaultTableSortColumn(): ?string
    {
        return 'deadline';
    }
}



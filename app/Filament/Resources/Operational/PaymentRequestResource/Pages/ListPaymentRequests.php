<?php

namespace App\Filament\Resources\Operational\PaymentRequestResource\Pages;

use App\Filament\Resources\PaymentRequestResource;
use EightyNine\ExcelImport\ExcelImportAction;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\Builder;


class ListPaymentRequests extends ListRecords
{
    protected static string $resource = PaymentRequestResource::class;

    public $statusFilter;

    protected $listeners = ['setStatusFilter'];

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->label('New')
                ->icon('heroicon-o-sparkles'),
            ExcelImportAction::make()
                ->color("success"),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return PaymentRequestResource::getWidgets();
    }

    public function setStatusFilter($filter)
    {
        $this->statusFilter = $filter === 'total' ? null : $filter;
        $this->resetPage();
    }

    protected function getTableQuery(): Builder
    {
        $query = self::getOriginalTable();

        if ($this->statusFilter) {
            $query->where('status', $this->statusFilter);
        }

        return $query;
    }

    public
    function getTabs(): array
    {
        return [
            null => Tab::make('All')->query(fn($query) => $query),
            'New' => Tab::make()->query(fn($query) => $query->where('status', 'pending')),
            'Processing' => Tab::make()->query(fn($query) => $query->where('status', 'processing')),
            'Allowed' => Tab::make()->query(fn($query) => $query->where('status', 'allowed')),
            'Approved' => Tab::make()->query(fn($query) => $query->where('status', 'approved')),
            'Rejected' => Tab::make()->query(fn($query) => $query->where('status', 'rejected')),
            'Fulfilled' => Tab::make()->query(fn($query) => $query->where('status', 'completed')),
            'Cancelled' => Tab::make()->query(fn($query) => $query->where('status', 'cancelled')),
        ];
    }

    private static function getOriginalTable()
    {
        return static::getResource()::getEloquentQuery();
    }
}

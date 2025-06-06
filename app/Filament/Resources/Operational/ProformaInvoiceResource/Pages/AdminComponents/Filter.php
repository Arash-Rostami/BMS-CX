<?php

namespace App\Filament\Resources\Operational\ProformaInvoiceResource\Pages\AdminComponents;

use App\Models\Buyer;
use App\Models\Category;
use App\Models\Grade;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Filters\Filter as FilamentFilter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Grouping\Group as Grouping;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;


trait Filter
{

    /**
     * @return Grouping
     */
    public static function groupProformaDateRecords(): Grouping
    {
        return Grouping::make('proforma_date')
            ->label('Pro forma Date')
            ->collapsible()
            ->getKeyFromRecordUsing(fn(Model $record) => ($record->proforma_date) ?? 'Not Given')
            ->getTitleFromRecordUsing(fn(Model $record): ?string => ucfirst(($record->proforma_date) ?? 'N/A'));
    }

    /**
     * @return Grouping
     */
    public static function groupProformaInvoiceRecords(): Grouping
    {
        return Grouping::make('proforma_number')
            ->label('Pro forma No.')
            ->collapsible()
            ->getKeyFromRecordUsing(fn(Model $record) => ($record->proforma_number) ?? 'Not Given')
            ->getTitleFromRecordUsing(fn(Model $record): ?string => ucfirst(($record->proforma_number) ?? 'N/A'));
    }


    /**
     * @return Grouping
     */
    public static function groupCategoryRecords(): Grouping
    {
        return Grouping::make('category_id')->label('Category')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => ucfirst($record->category->name ?? 'Not Given'));
    }

    /**
     * @return Grouping
     */
    public static function groupProductRecords(): Grouping
    {
        return Grouping::make('product_id')->label('Product')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => ucfirst($record->product->name ?? 'Not Given'));
    }

    /**
     * @return Grouping
     */
    public static function groupBuyerRecords(): Grouping
    {
        return Grouping::make('buyer_id')->label('Buyer')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => ucfirst(optional($record->buyer)->name ?? 'Not Given'));
    }

    /**
     * @return Grouping
     */
    public static function groupSupplierRecords(): Grouping
    {
        return Grouping::make('supplier_id')->label('Supplier')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => ucfirst(optional($record->supplier)->name ?? 'Not Given'));
    }

    /**
     * @return Grouping
     */
    public static function groupStatusRecords(): Grouping
    {
        return Grouping::make('status')->label('Status')->collapsible()
            ->getTitleFromRecordUsing(function (Model $record): string {
                $status = ucfirst($record->status ?? 'Not Given');
                return $status === 'Rejected' ? 'Declined/Cancelled' : $status;
            });
    }

    /**
     * @return Grouping
     */
    public static function groupContractRecords(): Grouping
    {
        return Grouping::make('contract_number')->label('Contract No.')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => ucfirst($record->contract_number ?? 'Not Defined'));
    }

    /**
     * @return Grouping
     */
    public static function groupPartRecords(): Grouping
    {
        return Grouping::make('part')->label('Part')->collapsible()
            ->getTitleFromRecordUsing(fn(Model $record): string => ucfirst($record->part ?? 'N/A'));
    }


    public static function filterProforma()
    {
        return FilamentFilter::make('proforma_date')
            ->form([
                DatePicker::make('proforma_from')
                    ->placeholder(fn($state): string => 'Dec 18, ' . now()->subYear()->format('Y')),
                DatePicker::make('proforma_until')
                    ->placeholder(fn($state): string => now()->format('M d, Y')),
            ])
            ->query(function (Builder $query, array $data): Builder {
                return $query
                    ->when(
                        $data['proforma_from'],
                        fn(Builder $query, $date): Builder => $query->whereDate('proforma_date', '>=', $date),
                    )
                    ->when(
                        $data['proforma_until'],
                        fn(Builder $query, $date): Builder => $query->whereDate('proforma_date', '<=', $date),
                    );
            })
            ->indicateUsing(function (array $data): array {
                $indicators = [];
                if ($data['proforma_from'] ?? null) {
                    $indicators['proforma_from'] = 'Proforma date from ' . Carbon::parse($data['proforma_from'])->toFormattedDateString();
                }
                if ($data['proforma_until'] ?? null) {
                    $indicators['proforma_until'] = 'Proforma date until ' . Carbon::parse($data['proforma_until'])->toFormattedDateString();
                }

                return $indicators;
            });
    }

    /**
     * @return mixed
     */
    public static function filterCategory()
    {
        return SelectFilter::make('category_id')
            ->label('Category')
            ->options(fn() => Category::pluck('name', 'id')->toArray())
            ->searchable()
            ->placeholder('All Categories');
    }

    /**
     * @return mixed
     */
    public static function filterProduct()
    {
        return SelectFilter::make('product_id')
            ->label('Product')
            ->options(fn() => Product::pluck('name', 'id')->toArray())
            ->searchable();
    }

    /**
     * @return mixed
     */
    public static function filterGrade()
    {
        return SelectFilter::make('grade_id')
            ->label('Grade')
            ->options(fn() => Grade::pluck('name', 'id')->toArray())
            ->searchable();
    }

    /**
     * @return mixed
     */
    public static function filterBuyer()
    {
        return SelectFilter::make('buyer_id')
            ->label('Buyer')
            ->options(fn() => Buyer::pluck('name', 'id')->toArray())
            ->searchable();
    }

    /**
     * @return mixed
     */
    public static function filterSupplier()
    {
        return SelectFilter::make('supplier_id')
            ->label('Supplier')
            ->options(fn() => Supplier::pluck('name', 'id')->toArray())
            ->searchable();
    }

    /**
     * @return mixed
     */
    public static function filterPart()
    {
        return SelectFilter::make('part')
            ->label('Part')
            ->options(array_combine(range(1, 100), range(1, 100)))
            ->placeholder('All Parts')
            ->searchable();
    }

    /**
     * @return mixed
     */
    public static function filterStatus()
    {
        return SelectFilter::make('status')
            ->label('Status')
            ->options([
                'pending' => 'Pending',
                'review' => 'Review',
                'approved' => 'Approved',
                'rejected' => 'Rejected/Cancelled',
                'fulfilled' => 'Completed',
            ])
            ->searchable();
    }

    /**
     * @return mixed
     */
    public static function filterCreator()
    {
        return SelectFilter::make('user_id')
            ->label('Created By')
            ->options(fn() => User::whereJsonContains('info->department', '6')
                ->get()
                ->mapWithKeys(fn($user) => [
                    $user->id => trim("{$user->first_name} {$user->middle_name} {$user->last_name}")
                ])
                ->toArray());
    }

    public static function filterVerified()
    {
        return SelectFilter::make('verified')
            ->form([
                Toggle::make('verified')
                    ->offColor('secondary')
                    ->onColor('success')
                    ->label('Verified'),
            ])->query(function (Builder $query, array $data): Builder {
                if (!empty($data['verified'])) {
                    $query->where('verified', true);
                }
                return $query;
            });
    }


    public static function filterTelexNeeded()
    {
        return FilamentFilter::make('completed_payment')
            ->label('Completed Payment')
            ->form([
                Toggle::make('completed_payment')
                    ->offColor('secondary')
                    ->onColor('primary')
                    ->label('Telex Needed'),
            ])
            ->query(function (Builder $query, array $data): Builder {
                if (!empty($data['completed_payment'])) {
                    $query->whereRaw(
                        "EXISTS (
                        SELECT 1
                        FROM orders o
                        WHERE o.proforma_invoice_id = proforma_invoices.id
                        AND EXISTS (
                            SELECT 1
                            FROM payment_requests pr
                            INNER JOIN payment_payment_request ppr ON pr.id = ppr.payment_request_id
                            INNER JOIN payments p ON ppr.payment_id = p.id
                            WHERE pr.order_id = o.id
                              AND pr.status = 'completed'
                              AND pr.type_of_payment = 'balance'
                              AND pr.deleted_at IS NULL
                              AND p.deleted_at IS NULL
                              AND p.date < CURDATE() - INTERVAL 3 DAY
                            GROUP BY pr.id, pr.requested_amount
                            HAVING SUM(p.amount) >= pr.requested_amount
                        )
                        AND NOT EXISTS (
                            SELECT 1
                            FROM attachments a
                            WHERE a.order_id = o.id
                              AND LOWER(a.name) LIKE '%telex-release%'
                              AND a.deleted_at IS NULL
                        )
                    )"
                    );
                }
                return $query;
            });
    }
}

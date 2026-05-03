<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Master\AccountResource\Pages\Admin;
use App\Filament\Resources\Master\AccountResource\Pages\ManageAccounts;
use App\Models\Account;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;


class AccountResource extends Resource
{
    protected static ?string $model = Account::class;
    protected static ?string $navigationIcon = 'heroicon-o-folder';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Admin::getName(),
            Admin::getType(),
            Admin::getStatus(),
            Admin::getParent(),
            Admin::getMeta(),
        ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with([
                'parent',
                'children',
                'ledgerEntries',
            ]);
    }

    public static function getNavigationGroup(): ?string
    {
        return config('nav.third_category');
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageAccounts::route('/'),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Stack::make([
                    Split::make([
                        Admin::showName(),
                        Admin::showStatus(),
                        Admin::showType(),
                    ]),
                    Panel::make([
                        Admin::showParent(),
                    ])->collapsible(),
                ])->space(3),
                Admin::showTimeStamp(),
            ])
            ->contentGrid(['md' => 2, 'xl' => 3])
            ->paginated([12, 24, 48])
            ->defaultGroup('parent.name')
            ->filters([
                SelectFilter::make('type')->options([
                    'person' => 'Person',
                    'agreement' => 'Agreement',
                    'invoice' => 'Invoice',
                ]),
                SelectFilter::make('status')->options([
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                ]),
            ])
            ->actions([
                EditAction::make(),
                Action::make('statementOfAccount')
                    ->label('Statement')
                    ->icon('heroicon-o-document-text')
                    ->color('info')
                    ->url(fn(Account $record): string => AccountStatementResource::getUrl('index', [
                        'tableFilters[account_id][value]' => $record->id,
                    ])),
            ])
            ->bulkActions([
                BulkActionGroup::make([DeleteBulkAction::make()]),
            ]);
    }
}

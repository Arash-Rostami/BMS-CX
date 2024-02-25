<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Core\UserResource\Pages\Admin;
use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;


class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Core Data';

    protected static ?string $recordTitleAttribute = 'first_name';

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record->fullName;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['first_name', 'last_name', 'middle_name'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
//                Section::make()
//                    ->schema([
                Tabs::make()
                    ->tabs([
                        /*First tab*/
                        Tabs\Tab::make('Personal ')
                            ->schema([
                                Section::make('👤')
                                    ->schema([
                                        Admin::getFirstName(),
                                        Admin::getMiddleName(),
                                        Admin::getLastName(),
                                        Admin::getPhoneNum()
                                    ])->columns(2)
                                    ->collapsible(),
                                Section::make(new HtmlString('<span class="grayscale">🛡️ </span>'))
                                    ->schema([
                                        Admin::getPassword(),
                                        Admin::getPassWordConfirmation(),
                                    ])->columns(2)
                                    ->collapsed()
                                    ->collapsible()
                                    ->visibleOn('create')
                                    ->visible(fn() => isUserAdmin()),
                            ])->columns(2),
                        /*Second tab*/
                        Tabs\Tab::make('Professional')
                            ->schema([
                                Section::make(new HtmlString('<span class="grayscale">💼 </span>'))
                                    ->schema([
                                        Grid::make(2)->schema([Admin::getCompany()]),
                                        Grid::make(1)->schema([Admin::getEmail()]),
                                    ])->collapsible(),
                                Section::make(new HtmlString('<span class="grayscale">🎭 </span>'))
                                    ->schema([
                                        Grid::make(2)->schema([Admin::getStatus()]),
                                        Grid::make(2)->schema([Admin::getRole()]),
                                    ])->collapsible()
                            ])->columns(1),
                        /*Third tab*/
                        Tabs\Tab::make('DevTools')
                            ->schema([
                                Textarea::make('info')
                                    ->label('🛈')
                                    ->hint('This part is in READONLY format for the development; do not touch!')
                                    ->readOnly()
                            ]),
                    ])
            ])->columns(1);
//            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                /*First panel*/
                Tables\Columns\Layout\Split::make([
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\Layout\Panel::make([
                            Admin::showAvatar(),
                            Admin::showFullName(),
                            Admin::showEmail(),
                            Admin::showPhone(),
                        ])
                    ]),
                    /*Second panel*/
                    Tables\Columns\Layout\Panel::make([
                        Tables\Columns\Layout\Split::make([
                            Tables\Columns\Layout\Stack::make([
                                Admin::showIP(),
                                Admin::showCompany()
                            ])->space(),
                            Tables\Columns\Layout\Stack::make([
                                Admin::showStatus(),
                                Admin::showRole(),
                            ]),
                        ]),
                    ])
                ]),
            ])
            ->poll(30)
            ->filters([
                Admin::filterRole(),
                Admin::filterStatus(),
                TrashedFilter::make(),

            ], layout: FiltersLayout::Modal)
            ->actions([
                Action::make('setting')
                    ->url(fn(User $record): string => route('filament.admin.resources.users.setting', ['record' => $record])),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Tables\Actions\ForceDeleteAction::make(),
                Tables\Actions\RestoreAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                    ExportBulkAction::make(),
                ])
            ])
            ->groups([
                Tables\Grouping\Group::make('company')
                    ->label('Company')
                    ->collapsible(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public function getHeader(): ?View
    {
        return view('filament.resources.user-resource.pages.settings');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Core\UserResource\Pages\ListUsers::route('/'),
            'create' => Core\UserResource\Pages\CreateUser::route('/create'),
            'edit' => Core\UserResource\Pages\EditUser::route('/{record}/edit'),
            'setting' => Core\UserResource\Pages\Settings::route('/abc/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'secondary';
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\Core\UserResource\Pages\Admin;
use App\Filament\Resources\Core\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ForceDeleteBulkAction;
use Filament\Tables\Actions\RestoreBulkAction;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\HtmlString;
use Illuminate\View\View;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;


class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'Core Data';
    protected static ?string $recordTitleAttribute = 'first_name';

    public ?string $tableSortColumn = 'email';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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
                                        Grid::make(2)->schema([Admin::getCompany(), Admin::getDepartment()]),
                                        Grid::make(1)->schema([Admin::getEmail()]),
                                    ])->collapsible(),
                                Section::make(new HtmlString('<span class="grayscale">🎭 </span>'))
                                    ->schema([
                                        Grid::make(2)->schema([Admin::getStatus(), Admin::getPosition()]),
                                        Grid::make(2)->schema([Admin::getRole()]),
                                    ])->collapsible()
                            ])->columns(1),
                    ])
            ])->columns(1);
    }

    public static function getClassicLayout(Table $table): Table
    {
        return $table
            ->columns([
                Admin::showAvatar(),
                Admin::showFullName(),
                Admin::showEmail(),
                Admin::showPhone(),
                Admin::showIP(),
                Admin::showCompany(),
                Admin::showStatus(),
                Admin::showRole(),
            ])->striped();
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('department')
            ->withoutGlobalScopes([
                SoftDeletingScope::class,
            ]);
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return "👨🏻‍💻 " . $record->fullName;
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['first_name', 'last_name', 'middle_name'];
    }

    public function getHeader(): ?View
    {
        return view('filament.resources.user-resource.pages.settings');
    }

    public static function getModernLayout(Table $table): Table
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
                Admin::showLastOnline()
            ]);
    }

    public static function getNavigationBadge(): ?string
    {
        $cacheKey = 'total_count_' . str('User')->slug();

        $count = Cache::remember($cacheKey, now()->addHours(24), function () {
            return static::getModel()::count();
        });

        return $count > 0 ? (string)$count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'secondary';
    }

    public static function getPages(): array
    {
        return [
            'index' => Core\UserResource\Pages\ListUsers::route('/'),
            'create' => Core\UserResource\Pages\CreateUser::route('/create'),
            'edit' => Core\UserResource\Pages\EditUser::route('/{record}/edit'),
            'version' => Core\UserResource\Pages\Versions::route('/versions'),
        ];
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function table(Table $table): Table
    {
        $table = self::configureCommonTableSettings($table);

        return (getTableDesign() != 'classic')
            ? self::getModernLayout($table)
            : self::getClassicLayout($table);

    }

    private static function configureCommonTableSettings(Table $table): Table
    {
        return $table
            ->filters([
                Admin::filterRole(),
                Admin::filterStatus(),
                Admin::filterDep(),
                TrashedFilter::make(),
            ], layout: FiltersLayout::Modal)
            ->actions([
//                Action::make('setting')
//                    ->url(fn(User $record): string => route('filament.admin.resources.users.setting', ['record' => $record])),
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
            ->defaultSort('first_name', 'asc')
            ->groups([
                Tables\Grouping\Group::make('company')
                    ->label('Company')
                    ->collapsible(),
            ])
            ->poll('900s')
            ->striped();
    }
}

<?php

namespace App\Filament\Resources\Master\AccountResource\Pages;

use App\Models\Account;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Support\Enums\FontWeight;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\HtmlString;

class Admin
{
    public static function getName(): TextInput
    {
        return TextInput::make('name')
            ->label(fn() => new HtmlString('<span class="grayscale">🏷️ </span><span class="text-primary-500 font-normal">Name</span>'))
            ->placeholder('Enter the complete name')
            ->helperText('Ensure NOT been recorded already')
            ->required()
            ->unique(ignoreRecord: true)
            ->validationMessages([
                'unique' => 'This name has already been recorded.',
            ])
            ->maxLength(255)
            ->dehydrateStateUsing(fn(?string $state) => ucwords($state));
    }

    public static function getType(): Select
    {
        return Select::make('type')
            ->label(fn () => new HtmlString('<span class="grayscale">📌 </span><span class="text-primary-500 font-normal">Type</span>'))
            ->placeholder('Select the applicable entity or document type')
            ->native(false)
            ->required()
            ->options(Account::typeOptions())
            ->createOptionForm([
                TextInput::make('type')
                    ->label(fn () => new HtmlString('<span class="grayscale">✨ </span><span class="text-primary-500 font-normal">New Type</span>'))
                    ->placeholder('Type name')
                    ->required()
                    ->maxLength(255)
                    ->unique(ignoreRecord: true)
                    ->validationMessages([
                        'unique' => 'This type has already been recorded.',
                    ]),
            ])
            ->createOptionUsing(fn (array $data) => trim($data['type']));
    }

    public static function getStatus(): Select
    {
        return Select::make('status')
            ->label(fn() => new HtmlString('<span class="grayscale">🚥 </span><span class="text-primary-500 font-normal">Status</span>'))
            ->placeholder('Change this account status')
            ->required()
            ->options([
                'active'   => 'Active',
                'inactive' => 'Inactive',
            ])
            ->default('active');
    }

    public static function getParent(): Select
    {
        return Select::make('parent_id')
            ->label(fn() => new HtmlString('<span class="grayscale">🔗 </span><span class="text-primary-500 font-normal">Parent Record</span>'))
            ->placeholder('Link to a parent record (optional)')
            ->relationship('parent', 'name')
            ->searchable()
            ->preload();
    }

    public static function getMeta(): KeyValue
    {
        return KeyValue::make('meta')
            ->label(fn() => new HtmlString('<span class="grayscale">🔧 </span><span class="text-primary-500 font-normal"> Extra</span>'))
            ->helperText('⇆ Store extra key value details for this record, i.e.  source, notes ...')
            ->columnSpanFull();
    }


    public static function showName(): TextColumn
    {
        return TextColumn::make('name')
            ->searchable()
            ->sortable()
            ->weight(FontWeight::SemiBold)
            ->icon('heroicon-o-folder');
    }

    public static function showParent(): TextColumn
    {
        return TextColumn::make('parent.name')
            ->label('Parent Account')
            ->searchable()
            ->sortable()
            ->badge()
            ->color('secondary')
            ->icon('heroicon-o-folder-open');
    }

    public static function showType(): TextColumn
    {
        return TextColumn::make('type')
            ->searchable()
            ->badge()
            ->color(fn(string $state): string => match ($state) {
                'person'    => 'info',
                'agreement' => 'warning',
                'invoice'   => 'success',
                default     => 'secondary',
            });
    }

    public static function showStatus(): TextColumn
    {
        return TextColumn::make('status')
            ->badge()
            ->color(fn(string $state): string => match ($state) {
                'active'   => 'success',
                'inactive' => 'danger',
                default    => 'secondary',
            });
    }

    public static function showTimeStamp(): TextColumn
    {
        return TextColumn::make('created_at')
            ->label('Created')
            ->dateTime()
            ->icon('heroicon-s-calendar-days')
            ->sortable()
            ->toggleable(isToggledHiddenByDefault: true);
    }
}

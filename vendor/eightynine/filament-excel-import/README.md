# Filament Excel Import

[![Latest Version on Packagist](https://img.shields.io/packagist/v/eightynine/filament-excel-import.svg?style=flat-square)](https://packagist.org/packages/eightynine/filament-excel-import)
[![Total Downloads](https://img.shields.io/packagist/dt/eightynine/filament-excel-import.svg?style=flat-square)](https://packagist.org/packages/eightynine/filament-excel-import)

This package adds a new feature to your filament resource, allowing you to easily import data to your model

_This package brings the maatwebsite/laravel-excel functionalities to filament. You can use all the maatwebsite/laravel-excel features in your laravel project_


## 🛠️ Be Part of the Journey

Hi, I'm Eighty Nine. I created excel import plugin to solve real problems I faced as a developer. Your sponsorship will allow me to dedicate more time to enhancing these tools and helping more people. [Become a sponsor](https://github.com/sponsors/eighty9nine) and join me in making a positive impact on the developer community.


## Installation

You can install the package via composer:

```bash
composer require eightynine/filament-excel-import
```

## Usage

Before using this action, make sure to allow [Mass Assignment](https://laravel.com/docs/10.x/eloquent#mass-assignment) for your model. If you are doing a custom import, this is not necessary.

```php
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = ['name', 'phone', 'email'];
}
```

For example, if you have a 'ClientResource' in your project, integrate the action into ListClients class as demonstrated below:

```php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListClients extends ListRecords
{
    protected static string $resource = ClientResource::class;

    protected function getHeaderActions(): array
    {
        return [
            \EightyNine\ExcelImport\ExcelImportAction::make()
                ->color("primary"),
            Actions\CreateAction::make(),
        ];
    }
}

```

### Customise Import Process

#### Using a closure
You can use a closure to process the collection after it has been imported.

```php

    protected function getHeaderActions(): array
    {
        return [
            \EightyNine\ExcelImport\ExcelImportAction::make()
                ->processCollectionUsing(function (string $modelClass, Collection $collection) {
                    // Do some stuff with the collection
                    return $collection;
                }),
            Actions\CreateAction::make(),
        ];
    }
```

#### Using your own Import class

If you wish to use your own import class to change the import procedure, you can create your own Import class.

```bash
php artisan make:import MyClientImport
```

Then in your action use your client import class

```php

    protected function getHeaderActions(): array
    {
        return [
            \EightyNine\ExcelImport\ExcelImportAction::make()
                ->slideOver()
                ->color("primary")
                ->use(App\Imports\MyClientImport::class),
            Actions\CreateAction::make(),
        ];
    }
```

### Form Customisation

You can customise the form by using the `beforeUploadField` and `afterUploadField` methods. These methods accept an array of fields that will be added to the form before and after the upload field. You can also use the `uploadField` method to customise the upload field.

```php

    protected function getHeaderActions(): array
    {
        return [
            \EightyNine\ExcelImport\ExcelImportAction::make()
                ->slideOver()
                ->color("primary")
                ->use(App\Imports\MyClientImport::class)
                // Add fields before the upload field
                ->beforeUploadField([
                    TextInput::make('default_password'),
                    TextInput::make('default_status'),
                ])
                // Or add fields after the upload field
                ->afterUploadField([
                    TextInput::make('default_password'),
                    TextInput::make('default_status'),
                ])
                // Or customise the upload field
                ->uploadField(
                    fn ($upload) => $upload
                    ->label("Some other label")
                )
                // Use the additional form fields data
                ->beforeImport(function (array $data, $livewire, $excelImportAction) {
                    $defaultStatus = $data['default_status'];
                    $defaultPassword = $data['default_password'];

                    // When adding the additional data, the data will be merged with 
                    // the row data when inserting into the database
                    $excelImportAction->additionalData([
                        'password' => $defaultPassword,
                        'status' => $defaultStatus
                    ]);

                    // When adding the custom import data, the data will be available in
                    // the custom import as $this->customImport data, when the custom import extends the
                    // Default import.
                    $excelImportAction->customImportData([
                        'other_details' => [ 1, 2, 3, 4],
                        'age' => 5
                    ]);

                    // Do some other stuff with the data before importing
                })
                ,
            Actions\CreateAction::make(),
        ];
    }
```

### Custom Upload Disk
To use a custom upload disk, you can publish the config file and customise the upload_disk config.

```bash
php artisan vendor:publish --tag=excel-import-config
```

Then in your config file, you can customise the upload_disk config.

```php
return [
    /**
     * File upload path
     * 
     * Customise the path where the file will be uploaded to, 
     * if left empty, config('filesystems.default') will be used
     */
    'upload_disk' => 's3',
];
```

### Performing Actions Before and After Import

You can perform actions before and after import by using the beforeImport and afterImport closures.

`$data` is the data that is submitted via the form, meaning the file upload is also available in `$data['upload']`, and `$livewire` is the Livewire instance that the action is being performed on (in this case, the ListClients class).

```php

    protected function getHeaderActions(): array
    {
        return [
            \EightyNine\ExcelImport\ExcelImportAction::make()
                ->slideOver()
                ->color("primary")
                ->use(App\Imports\MyClientImport::class)
                ->beforeImport(function ($data, $livewire, $excelImportAction) {
                    // Perform actions before import
                })
                ->afterImport(function ($data, $livewire, $excelImportAction) {
                    // Perform actions after import
                }),
            Actions\CreateAction::make(),
        ];
    }
```

### Data Validation

You can validate the data before importing by using the `validateUsing` method. This method accepts an array of rules that will be used to validate the data. You can use all the rules from the Laravel validation system.

```php

    protected function getHeaderActions(): array
    {
        return [
            \EightyNine\ExcelImport\ExcelImportAction::make()
                ->validateUsing([
                    'name' => 'required',
                    'email' => 'required|email',
                    'phone' => ['required','numeric'],
                ]),
            Actions\CreateAction::make(),
        ];
    }
```

### Mutating data before and after validation
In some cases you may want to mutate the data before or after validation, in order to achieve this, you can use `mutateBeforeValidationUsing` and `mutateAfterValidationUsing` functions methods.

```php

    \EightyNine\ExcelImport\ExcelImportAction::make()
        ->mutateBeforeValidationUsing(function(array $data): array{
            $data['date'] = Carbon::make((string) str($value)->replace('.', '-'));
            return $data;
        })
        ->validateUsing([
            'name' => 'required',
            'email' => 'required|email',
            'phone' => ['required','numeric'],
        ])
        ->mutateAfterValidationUsing(
            closure: function(array $data): array{
                $data['date'] = $data['date']->format('Y-m-d');
                return $data;
            },
            shouldRetainBeforeValidationMutation: true // if this is set, the mutations will be retained after validation (avoids repetition in/of afterValidation)
        ),

```

### Sample Excel File
You can allow users to download a sample excel file by using the `sampleExcel` method. This method accepts an array of data, a file name, an export class and a sample button label.

```php

use Filament\Forms\Components\Actions\Action;

protected function getHeaderActions(): array
{
    return [
        \EightyNine\ExcelImport\ExcelImportAction::make()
            ->sampleExcel(
                sampleData: [
                    ['name' => 'John Doe', 'email' => 'john@doe.com', 'phone' => '123456789'],
                    ['name' => 'Jane Doe', 'email' => 'jane@doe.com', 'phone' => '987654321'],
                ], 
                fileName: 'sample.xlsx', 
                exportClass: App\Exports\SampleExport::class, 
                sampleButtonLabel: 'Download Sample',
                customiseActionUsing: fn(Action $action) => $action->color('secondary')
                    ->icon('heroicon-m-clipboard')
                    ->requiresConfirmation(),
            ),
        Actions\CreateAction::make(),
    ];
}
```

### Using import action to import relationships

The import action can also be used to import relationships. This is done by using the `DefaultRelationshipImport` class in your relation manager.

```php

use EightyNine\ExcelImport\Tables\ExcelImportRelationshipAction;


class PostsRelationManager extends RelationManager
{
    protected static string $relationship = 'posts';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('title')
            ->columns([
                Tables\Columns\TextColumn::make('title'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                ExcelImportRelationshipAction::make()
                    ->slideOver()
                    ->color('primary')
                    ->validateUsing([
                        'title' => 'required',
                        'body' => 'required',
                    ]),
            ]);
    }
}
```

Everything behaves and can be modified similar to the `ExcelImportAction` class, except the `DefaultRelationshipImport` class is used instead of the `DefaultImport` class. So if you are implementing a custom import class, you will need to extend the `DefaultRelationshipImport` class instead of the `DefaultImport` class. 


## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](../../security/policy) on how to report security vulnerabilities.

## Credits

-   [Eighty Nine](https://github.com/eighty9nine)
-   [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

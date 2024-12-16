<?php

namespace EightyNine\ExcelImport;

use Closure;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DefaultRelationshipImport implements ToCollection, WithHeadingRow
{    
    protected array $customImportData = [];

    protected ?Closure $collectionMethod = null;

    protected ?Closure $afterValidationMutator = null;

    public function __construct(
        public string $model,
        public array $attributes = [],
        protected array $additionalData = [],
        public mixed $ownerRecord = null,
        public mixed $relationship = null,
        public ?Table $table = null
    ) {
    }

    public function setAdditionalData(array $additionalData): void
    {
        $this->additionalData = $additionalData;
    }
    
    public function setCustomImportData(array $customImportData): void
    {
        $this->customImportData = $customImportData;
    }

    public function setCollectionMethod(Closure $closure): void
    {
        $this->collectionMethod = $closure;
    }

    public function setAfterValidationMutator(Closure $closure): void
    {
        $this->afterValidationMutator = $closure;
    }

    public function collection(Collection $collection)
    {
        if(is_callable($this->collectionMethod)) {
            $collection = call_user_func(
                $this->collectionMethod, 
                $this->model,
                $collection,
                $this->additionalData,
                $this->afterValidationMutator,
                $this->relationship,
                $this->table
            );
        }else{
            foreach ($collection as $row) {

                $data = $row->toArray();
                if(filled($this->additionalData)) {
                    $data = array_merge($data, $this->additionalData);
                }

                if($this->afterValidationMutator){
                    $data = call_user_func(
                        $this->afterValidationMutator,
                        $data
                    );
                }

                // insert the relation data
                $pivotData = [];
                
                if ($this->relationship instanceof BelongsToMany) {
                    $pivotColumns = $this->relationship->getPivotColumns();

                    $pivotData = Arr::only($data, $pivotColumns);
                    $data = Arr::except($data, $pivotColumns);
                }

                if ($translatableContentDriver = $this->table->makeTranslatableContentDriver()) {
                    $record = $translatableContentDriver->makeRecord($this->model, $data);
                } else {
                    $record = new $this->model;
                    $record->fill($data);
                }

                if (
                    (! $this->relationship) ||
                    $this->relationship instanceof HasManyThrough
                ) {
                    $record->save();
                    continue;
                }

                if ($this->relationship instanceof BelongsToMany) {
                    $this->relationship->save($record, $pivotData);

                    continue;
                }

                /** @phpstan-ignore-next-line */
                $this->relationship->save($record);
            }
        }

        return $collection;
    }
}

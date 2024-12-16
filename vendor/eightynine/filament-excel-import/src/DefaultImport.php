<?php

namespace EightyNine\ExcelImport;

use Closure;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class DefaultImport implements ToCollection, WithHeadingRow
{
    protected array $additionalData = [];
    
    protected array $customImportData = [];

    protected ?Closure $collectionMethod = null;

    protected ?Closure $afterValidationMutator = null;

    public function __construct(
        public string $model,
        public array $attributes = []
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
                $this->afterValidationMutator
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
                $this->model::create($data);
            }
        }

        return $collection;
    }
}

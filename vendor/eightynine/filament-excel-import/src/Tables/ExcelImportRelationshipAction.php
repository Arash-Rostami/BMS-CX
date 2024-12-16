<?php

namespace EightyNine\ExcelImport\Tables;

use Closure;
use EightyNine\ExcelImport\Concerns\HasExcelImportAction;
use EightyNine\ExcelImport\DefaultRelationshipImport;
use Filament\Tables\Actions\Action;
use Maatwebsite\Excel\Facades\Excel;

class ExcelImportRelationshipAction extends Action
{
    use HasExcelImportAction;

    protected string $importClass = DefaultRelationshipImport::class;

    private function importData(): Closure
    {
        return function (array $data, $livewire): bool {
            if (is_callable($this->beforeImportClosure)) {
                call_user_func($this->beforeImportClosure, $data, $livewire, $this);
            }

            $importObject = new $this->importClass(
                method_exists($this, 'getModel') ? $this->getModel() : null,
                $this->importClassAttributes,
                $this->additionalData,
                method_exists($livewire, 'getOwnerRecord') ? $livewire->getOwnerRecord() : null,
                method_exists($livewire, 'getRelationship') ? $livewire->getRelationship() : null,
                method_exists($livewire, 'getTable') ? $livewire->getTable() : null
            );

            if(method_exists($importObject, 'setAdditionalData') && isset($this->additionalData)) {
                $importObject->setAdditionalData($this->additionalData);
            }

            if(method_exists($importObject, 'setCustomImportData') && isset($this->customImportData)) {
                $importObject->setCustomImportData($this->customImportData);
            }

            if(method_exists($importObject, 'setCollectionMethod') && isset($this->collectionMethod)) {
                $importObject->setCollectionMethod($this->collectionMethod);
            }

            if(method_exists($importObject, 'setAfterValidationMutator' && 
               (isset($this->afterValidationMutator) || $this->shouldRetainBeforeValidationMutation)
            )){
                $afterValidationMutator = $this->shouldRetainBeforeValidationMutation ? 
                        $this->beforeValidationMutator :
                        $this->afterValidationMutator;
                $importObject->setAfterValidationMutator($afterValidationMutator);
            }

            Excel::import($importObject, $data['upload']);

            if (is_callable($this->afterImportClosure)) {
                call_user_func($this->afterImportClosure, $data, $livewire);
            }
            return true;
        };
    }
}

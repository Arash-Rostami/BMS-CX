<?php

namespace EightyNine\ExcelImport\Concerns;

use Closure;
use EightyNine\ExcelImport\DefaultImport;
use Maatwebsite\Excel\Facades\Excel;

trait HasExcelImportAction
{
    use HasUploadForm,
        HasFormActionHooks,
        HasCustomCollectionMethod,
        CanCustomiseActionSetup,
        BelongsToTable,
        HasSampleExcelFile;

    protected array $importClassAttributes = [];

    public function use(string $class = null, ...$attributes): static
    {
        $this->importClass = $class ?: DefaultImport::class;
        $this->importClassAttributes = $attributes;

        return $this;
    }

    public static function getDefaultName(): ?string
    {
        return 'import';
    }

    public function action(Closure | string | null $action): static
    {
        if ($action !== 'importData') {
            throw new \Exception('You\'re unable to override the action for this plugin');
        }

        $this->action = $this->importData();

        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->icon('heroicon-o-arrow-down-tray')
            ->color('warning')
            ->form(fn () => $this->getDefaultForm())
            ->modalIcon('heroicon-o-arrow-down-tray')
            ->color('success')
            ->modalWidth('md')
            ->modalAlignment('center')
            ->modalHeading(fn ($livewire) => __('Import Excel'))
            ->modalDescription(__('Import data into database from excel file'))
            ->modalFooterActionsAlignment('right')
            ->closeModalByClickingAway(false)
            ->action('importData');
    }

    private function importData(): Closure
    {
        return function (array $data, $livewire): bool {
            if (is_callable($this->beforeImportClosure)) {
                call_user_func($this->beforeImportClosure, $data, $livewire, $this);
            }
            $importObject = new $this->importClass(
                method_exists($livewire, 'getModel') ? $livewire->getModel() : null,
                $this->importClassAttributes,
                $this->additionalData
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

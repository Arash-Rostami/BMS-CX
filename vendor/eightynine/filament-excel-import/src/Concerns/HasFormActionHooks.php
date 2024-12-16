<?php

namespace EightyNine\ExcelImport\Concerns;

use Closure;

trait HasFormActionHooks
{
    protected ?Closure $beforeImportClosure = null;

    protected ?Closure $afterImportClosure = null;

    protected array $additionalData = [];

    protected array $customImportData = [];

    public function additionalData(array $data): static
    {
        $this->additionalData = $data;

        return $this;
    }

    public function customImportData(array $data): static
    {
        $this->customImportData = $data;

        return $this;
    }

    public function beforeImport(Closure $closure): static
    {
        $this->beforeImportClosure = $closure;

        return $this;
    }

    public function afterImport(Closure $closure): static
    {
        $this->afterImportClosure = $closure;

        return $this;
    }
}

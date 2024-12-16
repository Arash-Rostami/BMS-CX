<?php

namespace EightyNine\ExcelImport\Concerns;

use Filament\Tables\Table;

trait BelongsToTable
{
    protected Table $table;

    public function table(Table $table): static
    {
        $this->table = $table;

        return $this;
    }
}
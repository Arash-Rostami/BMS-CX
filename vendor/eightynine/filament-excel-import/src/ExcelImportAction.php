<?php

namespace EightyNine\ExcelImport;

use Closure;
use EightyNine\ExcelImport\Concerns\HasExcelImportAction;
use Filament\Actions\Action;
use Maatwebsite\Excel\Facades\Excel;

class ExcelImportAction extends Action
{
    use HasExcelImportAction;

    protected string $importClass = DefaultImport::class;
}

<?php

namespace EightyNine\ExcelImport;

use Closure;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class ValidationImport implements ToCollection, WithHeadingRow
{
    public function __construct(
        public Closure $fail,
        public array $rules = [],
        public ?Closure $beforeValidationMutator
    ) {
    }

    public function collection(Collection $collection)
    {
        foreach ($collection as $index => $row) {
            $index = $index + 2;
            $data = $row->toArray();
            $data = isset($this->beforeValidationMutator) ? 
                call_user_func( $this->beforeValidationMutator, $data ) :
                $data;

            $validator = Validator::make($data, $this->rules);
            if ($validator->fails()) {
                call_user_func($this->fail, __("excel-import::excel-import.validation_failed", [
                    "row" => $index,
                    "messages" => $this->transformErrors($validator->errors()->getMessages()),
                ]));
            }
        }
        return $collection;
    }

    function transformErrors(array $errors): string
    {
        $transformed = [];

        foreach ($errors as $field => $messages) {
            foreach ($messages as $message) {
                $transformed[] = $message;
            }
        }
        return implode("\n", $transformed);
    }
}

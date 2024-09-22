<?php

namespace App\Rules;

use App\Models\Name;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueTitleInProformaInvoice implements ValidationRule
{
    /**
     * Run the validation rule.
     *
     * @param \Closure(string): \Illuminate\Translation\PotentiallyTranslatedString $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (Name::where('title', slugify($value))->where('module', 'ProformaInvoice')->exists()) {
            $fail('The title has already been taken; either select it from the list or make another one!');
        }
    }
}

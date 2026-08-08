<?php

namespace App\Exports\Concerns;

trait SanitizesSpreadsheetValues
{
    /**
     * Prefix formula-like user content so spreadsheet applications render it as text.
     */
    protected function spreadsheetValue(mixed $value): mixed
    {
        if (is_string($value) && preg_match('/^[=+\-@]/', $value)) {
            return "'".$value;
        }

        return $value;
    }
}

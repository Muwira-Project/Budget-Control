<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

#[Fillable(['type', 'year', 'last_number'])]
class NumberSequence extends Model
{
    /**
     * Atomically reserve the next number for the given type and year.
     */
    public static function next(string $type, ?string $year = null): int
    {
        $year ??= (string) now()->year;

        return DB::transaction(function () use ($type, $year) {
            $sequence = static::firstOrCreate(
                ['type' => $type, 'year' => $year],
                ['last_number' => 0],
            );

            $sequence->increment('last_number');

            return $sequence->fresh()->last_number;
        });
    }
}

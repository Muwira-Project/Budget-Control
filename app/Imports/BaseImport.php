<?php

namespace App\Imports;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

abstract class BaseImport implements ToCollection, WithHeadingRow
{
    /**
     * Expected column headers (slugified) for the import file.
     *
     * @var array<int, string>
     */
    protected array $expectedHeaders = [];

    /**
     * Number of rows successfully imported.
     */
    public int $successCount = 0;

    /**
     * List of failed rows with the reason for each failure.
     *
     * @var array<int, array{row: int, reason: string}>
     */
    public array $failures = [];

    /**
     * Fatal error message, if any (nothing was persisted).
     */
    public ?string $fatalError = null;

    /**
     * Read and validate the imported rows, then persist valid rows inside a transaction.
     */
    public function collection(Collection $rows): void
    {
        $dataRows = $rows->filter(fn (Collection $row) => $row->filter(fn ($value) => $value !== null && $value !== '')->isNotEmpty());

        if ($dataRows->isEmpty()) {
            $this->fatalError = 'The file contains no data.';

            return;
        }

        $actualHeaders = $rows->first()->keys()->all();

        if ($this->expectedHeaders !== $actualHeaders) {
            $this->fatalError = 'The header does not match the template. '
                .'Expected: '.implode(', ', $this->expectedHeaders)
                .'. Found: '.implode(', ', $actualHeaders).'.';

            return;
        }

        $validRows = [];
        $seenKeys = [];

        foreach ($dataRows->values() as $index => $row) {
            $rowNumber = $index + 2;

            [$valid, $data, $reason] = $this->validateRow($row, $seenKeys);

            if ($valid) {
                $validRows[] = $data;
            } else {
                $this->failures[] = ['row' => $rowNumber, 'reason' => $reason];
            }
        }

        if ($validRows === []) {
            $this->successCount = 0;

            return;
        }

        try {
            DB::transaction(function () use ($validRows): void {
                foreach ($validRows as $data) {
                    $this->persist($data);
                }
            });
        } catch (\Throwable $e) {
            $this->fatalError = 'A fatal error occurred while saving data; the entire process was cancelled: '.$e->getMessage();
            $this->successCount = 0;
            $this->failures = [];

            return;
        }

        $this->successCount = count($validRows);
    }

    /**
     * Validate a single row.
     *
     * @return array{0: bool, 1: array<string, mixed>|null, 2: string|null}
     */
    abstract protected function validateRow(Collection $row, array &$seenKeys): array;

    /**
     * Normalize the nominal value to a float, or null when it is not numeric.
     */
    protected function normalizeNominal(mixed $value): ?float
    {
        if ($value === null || trim((string) $value) === '') {
            return null;
        }

        $cleaned = str_replace(['Rp ', 'Rp', ' ', ','], ['', '', '', ''], (string) $value);

        return is_numeric($cleaned) ? (float) $cleaned : null;
    }

    /**
     * Persist a validated row.
     *
     * @param  array<string, mixed>  $data
     */
    abstract protected function persist(array $data): void;
}

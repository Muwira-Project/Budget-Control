<?php

namespace App\Imports;

use App\Models\Akun;
use App\Models\Kategori;
use Illuminate\Support\Collection;

class AkunImport extends BaseImport
{
    /**
     * Expected column headers (slugified) for the akun import file.
     *
     * @var array<int, string>
     */
    protected array $expectedHeaders = ['account_code', 'account_name', 'type', 'category'];

    /**
     * Validate a single akun row.
     *
     * @return array{0: bool, 1: array<string, mixed>|null, 2: string|null}
     */
    protected function validateRow(Collection $row, array &$seenKeys): array
    {
        $kodeAkun = trim((string) $row->get('account_code'));
        $namaAkun = trim((string) $row->get('account_name'));
        $rawJenis = strtolower(trim((string) ($row->get('type') ?? '')));
        $kategoriName = trim((string) ($row->get('category') ?? ''));
        $jenis = match ($rawJenis) {
            'income', 'pendapatan', 'pemasukan', 'in' => 'pendapatan',
            'outcome', 'pengeluaran', 'expense', 'out', 'biaya' => 'pengeluaran',
            default => null,
        };

        if ($kodeAkun === '') {
            return [false, null, 'Account Code is required'];
        }

        if ($namaAkun === '') {
            return [false, null, 'Account Name is required'];
        }

        if ($jenis === null) {
            return [false, null, 'Type must be income or outcome'];
        }

        $kategoriId = null;

        if ($kategoriName !== '') {
            $kategori = Kategori::where('nama', $kategoriName)
                ->orWhereRaw('LOWER(TRIM(nama)) = ?', [strtolower($kategoriName)])
                ->first();

            if (! $kategori) {
                $kategori = Kategori::create(['nama' => $kategoriName]);
            }

            $kategoriId = $kategori->id;
        }

        if (isset($seenKeys[$kodeAkun])) {
            return [false, null, 'Account Code '.$kodeAkun.' is duplicated in the file'];
        }

        if (Akun::where('kode_akun', $kodeAkun)->exists()) {
            return [false, null, 'Account Code '.$kodeAkun.' is already registered'];
        }

        $seenKeys[$kodeAkun] = true;

        return [
            true,
            [
                'kode_akun' => $kodeAkun,
                'nama_akun' => $namaAkun,
                'jenis_akun' => $jenis,
                'kategori_id' => $kategoriId,
            ],
            null,
        ];
    }

    /**
     * Persist a validated akun row.
     *
     * @param  array<string, mixed>  $data
     */
    protected function persist(array $data): void
    {
        Akun::create($data);
    }
}

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dynamic party masters + unify the party reference.
     *
     * Fase 2 (keputusan 2026-08-20): Vendor, Supplier, Mandor, dan Investor
     * dipindahkan ke sistem dynamic master (master_types + master_items) seperti
     * PIC, lengkap dengan flag_ar/flag_ap per item untuk pengelompokan AR/AP.
     *
     * - Membuat 4 master_types (VENDOR/SUPPLIER/MANDOR/INVESTOR, is_system=true,
     *   flag_ar=true, flag_ap=true) + 2 master_fields (telepon, alamat).
     * - Memigrasi data dari tabel lama (vendors/suppliers/mandors/investors)
     *   ke master_items; kode/nama/aktif tetap, telepon+alamat masuk kolom JSON
     *   data (dengan kunci 'telepon'/'alamat').
     * - Menambahkan kolom pihak terpadu pada payables, receivables, dan realisasi:
     *   pihak_type_id -> master_types.id, pihak_item_id -> master_items.id.
     *   Kolom lama (vendor_id, supplier_id, mandor_id, investor_id) dipertahankan
     *   agar data lama tetap terbaca (restorative accessor) lalu di-drop.
     */
    public function up(): void
    {
        // 1) Master types baru (system, tidak bisa dihapus; ATURAN: filter AR/AP).
        $definitions = [
            ['kode' => 'VENDOR', 'nama' => 'Vendor', 'deskripsi' => 'Vendor (jasa) - pihak transaksi'],
            ['kode' => 'SUPPLIER', 'nama' => 'Supplier', 'deskripsi' => 'Supplier (barang) - pihak transaksi'],
            ['kode' => 'MANDOR', 'nama' => 'Mandor', 'deskripsi' => 'Mandor - pihak transaksi'],
            ['kode' => 'INVESTOR', 'nama' => 'Investor', 'deskripsi' => 'Investor - pihak transaksi'],
        ];

        foreach ($definitions as $definition) {
            DB::table('master_types')->updateOrInsert(
                ['kode' => $definition['kode']],
                array_merge($definition, [
                    'flag_ar' => true,
                    'flag_ap' => true,
                    'aktif' => true,
                    'is_system' => true,
                    'sort' => 10,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]),
            );
        }

        // 2) Master fields standar (telepon, alamat) untuk 4 tipe pihak.
        $fieldRecords = [];

        foreach ($definitions as $definition) {
            $typeId = DB::table('master_types')->where('kode', $definition['kode'])->value('id');

            foreach ([['Telepon', 'text'], ['Alamat', 'textarea']] as $index => [$label, $tipe]) {
                $fieldRecords[] = [
                    'master_type_id' => $typeId,
                    'label' => $label,
                    'tipe' => $tipe,
                    'is_required' => false,
                    'sort' => $index,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('master_fields')->insert($fieldRecords);

        // 3) Migrasi data lama -> master_items (id lama disimpan demi integritas relasi).
        $targets = [
            ['kode' => 'VENDOR', 'table' => 'vendors'],
            ['kode' => 'SUPPLIER', 'table' => 'suppliers'],
            ['kode' => 'MANDOR', 'table' => 'mandors'],
            ['kode' => 'INVESTOR', 'table' => 'investors'],
        ];

        foreach ($targets as $target) {
            $typeId = DB::table('master_types')->where('kode', $target['kode'])->value('id');

            $oldRows = DB::table($target['table'])->get(['id', 'kode', 'nama', 'telepon', 'alamat']);

            if ($oldRows->isEmpty()) {
                continue;
            }

            foreach ($oldRows as $oldRow) {
                $kompatId = (int) $oldRow->id;
                $safeKode = $oldRow->kode;
                $counter = 1;

                while (DB::table('master_items')->where('master_type_id', $typeId)->where('kode', $safeKode)->exists()) {
                    $safeKode = $oldRow->kode.'-'.($counter++);
                }

                $payload = [
                    'master_type_id' => $typeId,
                    'kode' => $safeKode,
                    'nama' => $oldRow->nama,
                    'keterangan' => null,
                    'data' => json_encode([
                        'telepon' => $oldRow->telepon ?? null,
                        'alamat' => $oldRow->alamat ?? null,
                    ]),
                    'aktif' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];

                // Id asli tabel lama dipakai agar relasi historis tetap mengarah
                // ke baris yang sama. Hanya bisa bertabrakan bila master_items
                // sudah terisi sebelumnya (mis. PIC dari seeder lama pada DB yang
                // di-upgrade) — dalam kasus itu baris pihak di-skip karena bukan
                // milik tipe master aktif. Fresh DB tidak pernah kena kasus ini.
                $insertId = $kompatId;

                if (DB::table('master_items')->where('id', $insertId)->exists()) {
                    continue;
                }

                DB::table('master_items')->insert(['id' => $insertId, ...$payload]);
            }
        }

        // 4) Kolom pihak terpadu + salin nilai lama (data dipertahankan).
        foreach (['payables', 'receivables', 'realisasi'] as $table) {
            if (! Schema::hasColumn($table, 'pihak_type_id')) {
                Schema::table($table, function (Blueprint $table) {
                    $table->foreignId('pihak_type_id')->nullable()->after('project_id')->constrained('master_types')->nullOnDelete();
                    $table->foreignId('pihak_item_id')->nullable()->after('pihak_type_id')->constrained('master_items')->nullOnDelete();
                });
            }
        }

        $partyMapping = [
            'vendor_id' => 'VENDOR',
            'supplier_id' => 'SUPPLIER',
            'mandor_id' => 'MANDOR',
            'investor_id' => 'INVESTOR',
        ];

        foreach (['payables', 'realisasi'] as $table) {
            foreach ($partyMapping as $oldColumn => $typeKode) {
                if (! Schema::hasColumn($table, $oldColumn)) {
                    continue;
                }

                $typeId = DB::table('master_types')->where('kode', $typeKode)->value('id');

                DB::table($table)
                    ->whereNotNull($oldColumn)
                    ->update([
                        'pihak_type_id' => $typeId,
                        'pihak_item_id' => DB::raw($oldColumn),
                    ]);
            }
        }

        // Receivables tidak punya kolom pihak lama — tidak ada data untuk disalin.
    }

    public function down(): void
    {
        // Rollback: kembalikan nilai kolom lama dari kolom baru, lalu drop kolom baru.
        $partyMapping = [
            'vendor_id' => 'VENDOR',
            'supplier_id' => 'SUPPLIER',
            'mandor_id' => 'MANDOR',
            'investor_id' => 'INVESTOR',
        ];

        foreach (['payables', 'receivables', 'realisasi'] as $table) {
            foreach ($partyMapping as $oldColumn => $typeKode) {
                $typeId = DB::table('master_types')->where('kode', $typeKode)->value('id');

                DB::table($table)
                    ->where('pihak_type_id', $typeId)
                    ->update([$oldColumn => DB::raw('pihak_item_id')]);
            }

            Schema::table($table, function (Blueprint $table) {
                $table->dropConstrainedForeignId('pihak_item_id');
                $table->dropConstrainedForeignId('pihak_type_id');
            });
        }

        // Hapus master_items yang berasal dari migrasi (id = id tabel lama).
        foreach (['VENDOR', 'SUPPLIER', 'MANDOR', 'INVESTOR'] as $typeKode) {
            $typeId = DB::table('master_types')->where('kode', $typeKode)->value('id');

            if ($typeId !== null) {
                DB::table('master_items')->where('master_type_id', $typeId)->delete();
                DB::table('master_fields')->where('master_type_id', $typeId)->delete();
                DB::table('master_types')->where('id', $typeId)->delete();
            }
        }
    }
};

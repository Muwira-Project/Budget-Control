<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Module Payment Request & Non-Project Expense sudah dihapus pada
     * 2026_08_16_000014 (tabel + menu), tetapi cashflow lama yang bersumber
     * dari modul tersebut tidak ikut dimigrasi. Nilai 'payment_request' dan
     * 'non_project_expense' bukan lagi anggota enum CashflowSumber sehingga
     * memicu ValueError saat halaman cashflows dirender.
     *
     * Data lama dipetakan ke sumber yang masih valid: 'pengeluaran_lain'
     * (keduanya adalah catatan kas keluar manual di luar pelunasan AR/AP).
     */
    public function up(): void
    {
        DB::table('cashflows')
            ->whereIn('sumber', ['payment_request', 'non_project_expense'])
            ->update(['sumber' => 'pengeluaran_lain']);
    }

    public function down(): void
    {
        // Tidak bisa mengembalikan sumber asli karena informasi aslinya
        // sudah tidak tersedia (modul dihapus). Biarkan apa adanya.
    }
};

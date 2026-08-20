<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-item AR/AP flags on master_items.
     *
     * Keputusan 2026-08-20 (opsi A): setiap master item (vendor, supplier,
     * mandor, investor) dapat dikelompokkan sebagai AR, AP, atau keduanya.
     * - flag_ar = item dapat dipilih sebagai pihak pada Receivable (AR).
     * - flag_ap = item dapat dipilih sebagai pihak pada Payable (AP).
     * Nilai null berarti netral (tidak dibatasi); false berarti tidak muncul
     * pada dropdown AR/AP terkait.
     */
    public function up(): void
    {
        Schema::table('master_items', function (Blueprint $table) {
            $table->boolean('flag_ar')->nullable()->after('aktif');
            $table->boolean('flag_ap')->nullable()->after('flag_ar');
        });
    }

    public function down(): void
    {
        Schema::table('master_items', function (Blueprint $table) {
            $table->dropColumn(['flag_ar', 'flag_ap']);
        });
    }
};

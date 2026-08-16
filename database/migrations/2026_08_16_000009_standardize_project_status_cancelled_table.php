<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Standardisasi kamus status (Fase A): nilai project "cancel" menjadi "cancelled"
     * agar seragam dengan Payment Request dan Settlement.
     */
    public function up(): void
    {
        DB::table('projects')->where('status', 'cancel')->update(['status' => 'cancelled']);
    }

    public function down(): void
    {
        DB::table('projects')->where('status', 'cancelled')->update(['status' => 'cancel']);
    }
};

<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Tambahan field Project: PIC, Project Category, Sub Work, Periode, Status (done/progress/cancel).
     */
    public function up(): void
    {
        // Seed master type "Project Category" untuk dropdown kategori project (via dynamic master).
        if (! DB::table('master_types')->where('kode', 'PROJECT_CATEGORY')->exists()) {
            DB::table('master_types')->insert([
                'kode' => 'PROJECT_CATEGORY',
                'nama' => 'Project Category',
                'deskripsi' => 'Kategori project (master dinamis)',
                'flag_ar' => false,
                'flag_ap' => false,
                'aktif' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        Schema::table('projects', function (Blueprint $table) {
            $table->string('pic', 255)->nullable()->after('lokasi');
            $table->foreignId('project_category_id')->nullable()->after('pic')->constrained('master_items')->nullOnDelete();
            $table->string('sub_work', 500)->nullable()->after('project_category_id');
            $table->string('periode', 100)->nullable()->after('sub_work');
        });

        // Migrasi nilai status lama ke nilai baru: active -> progress, completed -> done.
        DB::table('projects')->where('status', 'active')->update(['status' => 'progress']);
        DB::table('projects')->where('status', 'completed')->update(['status' => 'done']);

        Schema::table('projects', function (Blueprint $table) {
            $table->string('status')->default('progress')->change();
        });
    }

    public function down(): void
    {
        DB::table('projects')->where('status', 'progress')->update(['status' => 'active']);
        DB::table('projects')->where('status', 'done')->update(['status' => 'completed']);

        Schema::table('projects', function (Blueprint $table) {
            $table->dropConstrainedForeignId('project_category_id');
            $table->dropColumn(['pic', 'sub_work', 'periode']);
        });

        DB::table('master_types')->where('kode', 'PROJECT_CATEGORY')->delete();
    }
};
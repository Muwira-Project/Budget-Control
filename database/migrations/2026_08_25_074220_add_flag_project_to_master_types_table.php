<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('master_types', function (Blueprint $table) {
            $table->boolean('flag_project')->default(false)->after('flag_ap');
        });
    }

    public function down(): void
    {
        Schema::table('master_types', function (Blueprint $table) {
            $table->dropColumn('flag_project');
        });
    }
};

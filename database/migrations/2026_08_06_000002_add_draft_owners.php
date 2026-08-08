<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('project_akuns', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
        });

        Schema::table('payment_requests', function (Blueprint $table) {
            $table->foreignId('created_by')->nullable()->after('status')->constrained('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('project_akuns', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
        });

        Schema::table('payment_requests', function (Blueprint $table) {
            $table->dropConstrainedForeignId('created_by');
        });
    }
};

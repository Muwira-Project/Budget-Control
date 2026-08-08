<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Add indexes to projects table
        Schema::table('projects', function (Blueprint $table) {
            if (!$this->indexExists('projects', 'projects_kode_index')) {
                $table->index('kode');
            }
            if (!$this->indexExists('projects', 'projects_status_index')) {
                $table->index('status');
            }
            if (!$this->indexExists('projects', 'projects_tanggal_mulai_index')) {
                $table->index('tanggal_mulai');
            }
            if (!$this->indexExists('projects', 'projects_target_selesai_index')) {
                $table->index('target_selesai');
            }
        });

        // Add indexes to realisasi table
        Schema::table('realisasi', function (Blueprint $table) {
            if (!$this->indexExists('realisasi', 'realisasi_tanggal_index')) {
                $table->index('tanggal');
            }
            if (!$this->indexExists('realisasi', 'realisasi_kategori_id_index')) {
                $table->index('kategori_id');
            }
            if (!$this->indexExists('realisasi', 'realisasi_project_id_index')) {
                $table->index('project_id');
            }
            if (!$this->indexExists('realisasi', 'realisasi_akun_id_index')) {
                $table->index('akun_id');
            }
        });

        // Add composite indexes
        Schema::table('realisasi', function (Blueprint $table) {
            if (!$this->indexExists('realisasi', 'realisasi_project_id_tanggal_index')) {
                $table->index(['project_id', 'tanggal']);
            }
            if (!$this->indexExists('realisasi', 'realisasi_kategori_id_tanggal_index')) {
                $table->index(['kategori_id', 'tanggal']);
            }
        });

        // Add indexes to project_akuns table
        Schema::table('project_akuns', function (Blueprint $table) {
            if (!$this->indexExists('project_akuns', 'project_akuns_status_index')) {
                $table->index('status');
            }
            if (!$this->indexExists('project_akuns', 'project_akuns_project_id_index')) {
                $table->index('project_id');
            }
            if (!$this->indexExists('project_akuns', 'project_akuns_akun_id_index')) {
                $table->index('akun_id');
            }
        });

        // Add indexes to payables table
        Schema::table('payables', function (Blueprint $table) {
            if (!$this->indexExists('payables', 'payables_status_index')) {
                $table->index('status');
            }
            if (!$this->indexExists('payables', 'payables_project_id_index')) {
                $table->index('project_id');
            }
            if (!$this->indexExists('payables', 'payables_status_tanggal_index')) {
                $table->index(['status', 'tanggal']);
            }
        });

        // Add indexes to receivables table
        Schema::table('receivables', function (Blueprint $table) {
            if (!$this->indexExists('receivables', 'receivables_project_id_index')) {
                $table->index('project_id');
            }
            if (!$this->indexExists('receivables', 'receivables_project_id_tanggal_index')) {
                $table->index(['project_id', 'tanggal']);
            }
        });

        // Add indexes to payment_requests table
        Schema::table('payment_requests', function (Blueprint $table) {
            if (!$this->indexExists('payment_requests', 'payment_requests_status_index')) {
                $table->index('status');
            }
            if (!$this->indexExists('payment_requests', 'payment_requests_project_id_index')) {
                $table->index('project_id');
            }
            if (!$this->indexExists('payment_requests', 'payment_requests_status_tanggal_index')) {
                $table->index(['status', 'tanggal']);
            }
        });

        // Add indexes to cashflows table
        Schema::table('cashflows', function (Blueprint $table) {
            if (!$this->indexExists('cashflows', 'cashflows_tanggal_index')) {
                $table->index('tanggal');
            }
            if (!$this->indexExists('cashflows', 'cashflows_jenis_index')) {
                $table->index('jenis');
            }
            if (!$this->indexExists('cashflows', 'cashflows_jenis_tanggal_index')) {
                $table->index(['jenis', 'tanggal']);
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            if ($this->indexExists('projects', 'projects_kode_index')) {
                $table->dropIndex(['kode']);
            }
            if ($this->indexExists('projects', 'projects_tanggal_mulai_index')) {
                $table->dropIndex(['tanggal_mulai']);
            }
            if ($this->indexExists('projects', 'projects_target_selesai_index')) {
                $table->dropIndex(['target_selesai']);
            }
        });

        Schema::table('realisasi', function (Blueprint $table) {
            if ($this->indexExists('realisasi', 'realisasi_tanggal_index')) {
                $table->dropIndex(['tanggal']);
            }
            if ($this->indexExists('realisasi', 'realisasi_kategori_id_index')) {
                $table->dropIndex(['kategori_id']);
            }
            if ($this->indexExists('realisasi', 'realisasi_akun_id_index')) {
                $table->dropIndex(['akun_id']);
            }
            if ($this->indexExists('realisasi', 'realisasi_project_id_tanggal_index')) {
                $table->dropIndex(['project_id', 'tanggal']);
            }
            if ($this->indexExists('realisasi', 'realisasi_kategori_id_tanggal_index')) {
                $table->dropIndex(['kategori_id', 'tanggal']);
            }
        });

        Schema::table('project_akuns', function (Blueprint $table) {
            if ($this->indexExists('project_akuns', 'project_akuns_status_index')) {
                $table->dropIndex(['status']);
            }
            if ($this->indexExists('project_akuns', 'project_akuns_akun_id_index')) {
                $table->dropIndex(['akun_id']);
            }
        });

        Schema::table('payables', function (Blueprint $table) {
            if ($this->indexExists('payables', 'payables_status_tanggal_index')) {
                $table->dropIndex(['status', 'tanggal']);
            }
        });

        Schema::table('receivables', function (Blueprint $table) {
            if ($this->indexExists('receivables', 'receivables_project_id_tanggal_index')) {
                $table->dropIndex(['project_id', 'tanggal']);
            }
        });

        Schema::table('payment_requests', function (Blueprint $table) {
            if ($this->indexExists('payment_requests', 'payment_requests_status_index')) {
                $table->dropIndex(['status']);
            }
            if ($this->indexExists('payment_requests', 'payment_requests_status_tanggal_index')) {
                $table->dropIndex(['status', 'tanggal']);
            }
        });

        Schema::table('cashflows', function (Blueprint $table) {
            if ($this->indexExists('cashflows', 'cashflows_tanggal_index')) {
                $table->dropIndex(['tanggal']);
            }
            if ($this->indexExists('cashflows', 'cashflows_jenis_index')) {
                $table->dropIndex(['jenis']);
            }
            if ($this->indexExists('cashflows', 'cashflows_jenis_tanggal_index')) {
                $table->dropIndex(['jenis', 'tanggal']);
            }
        });
    }

    /**
     * Check if an index exists on a table.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        return collect(\DB::select("PRAGMA index_list({$table})"))->pluck('name')->contains($indexName);
    }
};

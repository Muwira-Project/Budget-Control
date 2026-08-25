<?php

use App\Models\MasterItem;
use App\Models\MasterType;
use App\Models\Project;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Create MasterType Division
        $divisionType = MasterType::firstOrCreate(
            ['kode' => 'DIVISION'],
            [
                'nama' => 'Division',
                'deskripsi' => 'Project division/department',
                'flag_project' => true,
                'flag_ar' => false,
                'flag_ap' => false,
                'aktif' => true,
                'is_system' => true,
                'sort' => 5,
            ],
        );

        // 2. Create default MasterItems
        $defaultItems = [
            ['kode' => 'CONSTRUCTION', 'nama' => 'Construction'],
            ['kode' => 'MEP', 'nama' => 'MEP'],
            ['kode' => 'CIVIL', 'nama' => 'Civil'],
            ['kode' => 'ARCHITECTURE', 'nama' => 'Architecture'],
            ['kode' => 'INTERIOR', 'nama' => 'Interior'],
            ['kode' => 'OTHERS', 'nama' => 'Others'],
        ];

        $itemMap = [];
        foreach ($defaultItems as $item) {
            $masterItem = MasterItem::firstOrCreate(
                ['master_type_id' => $divisionType->id, 'kode' => $item['kode']],
                ['nama' => $item['nama'], 'aktif' => true],
            );
            $itemMap[strtolower($item['nama'])] = $masterItem->id;
        }

        // 3. Add division_id column
        Schema::table('projects', function (Blueprint $table) {
            $table->foreignId('division_id')
                ->nullable()
                ->constrained('master_items')
                ->nullOnDelete()
                ->after('lokasi');
        });

        // 4. Migrate existing data: string devisi -> division_id
        Project::whereNotNull('devisi')->chunk(100, function ($projects) use ($itemMap) {
            foreach ($projects as $project) {
                $key = strtolower(trim($project->devisi));
                $divisionId = $itemMap[$key] ?? $itemMap['others'];
                if ($divisionId) {
                    $project->division_id = $divisionId;
                    $project->save();
                }
            }
        });

        // 5. Drop old string column
        Schema::table('projects', function (Blueprint $table) {
            $table->dropColumn('devisi');
        });
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->string('devisi', 255)->nullable()->after('lokasi');
            $table->dropConstrainedForeignId('division_id');
        });

        // Restore string data from division relation
        Project::with('division')->chunk(100, function ($projects) {
            foreach ($projects as $project) {
                $project->update(['devisi' => $project->division?->nama]);
            }
        });
    }
};
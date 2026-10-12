<?php

namespace App\Livewire\Backups;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Backup\Helpers\Format;
use Symfony\Component\HttpFoundation\StreamedResponse;
use ZipArchive;
use Livewire\Attributes\Validate;
use Livewire\WithFileUploads;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithFileUploads;
    public ?string $message = null;

    public ?string $error = null;

    #[Validate('nullable|file|mimes:zip|max:5120000')]
    public $uploadedBackupFile = null;

    public function mount(): void
    {
        abort_unless(Gate::allows('accessBackups', Backup::class), 403);
    }

    #[Computed]
    public function backups(): array
    {
        $folder = config('backup.backup.name');

        return collect(Storage::disk('local')->allFiles($folder))
            ->filter(fn (string $path) => str_ends_with($path, '.zip'))
            ->map(fn (string $path) => [
                'path' => $path,
                'name' => basename($path),
                'size' => Format::humanReadableSize(Storage::disk('local')->size($path)),
                'modified_at' => Storage::disk('local')->lastModified($path),
            ])
            ->sortByDesc('modified_at')
            ->values()
            ->all();
    }

    public function createBackup(): void
    {
        abort_unless(Gate::allows('accessBackups', Backup::class), 403);

        try {
            Artisan::call('backup:run', [
                '--only-db' => true,
                '--disable-notifications' => true,
            ]);

            $this->message = 'Backup database berhasil dibuat.';
            $this->error = null;
            unset($this->backups);
        } catch (\Throwable $exception) {
            $this->error = 'Backup gagal dibuat: '.$exception->getMessage();
            $this->message = null;
        }
    }

    public function cleanOldBackups(): void
    {
        abort_unless(Gate::allows('accessBackups', Backup::class), 403);

        try {
            Artisan::call('backup:clean', ['--disable-notifications' => true]);

            $this->message = 'Backup lama berhasil dibersihkan.';
            $this->error = null;
            unset($this->backups);
        } catch (\Throwable $exception) {
            $this->error = 'Pembersihan backup gagal: '.$exception->getMessage();
            $this->message = null;
        }
    }

    public function download(string $path): StreamedResponse
    {
        abort_unless($this->isValidBackupPath($path), 404);

        return Storage::disk('local')->download($path);
    }

    public function delete(string $path): void
    {
        abort_unless(Gate::allows('accessBackups', Backup::class), 403);
        abort_unless($this->isValidBackupPath($path), 404);

        Storage::disk('local')->delete($path);

        $this->message = 'Backup berhasil dihapus.';
        $this->error = null;
        unset($this->backups);
    }

    public function restore(string $path): void
    {
        abort_unless(Gate::allows('accessBackups', Backup::class), 403);
        abort_unless($this->isValidBackupPath($path), 404);

        try {
            $backupPath = Storage::disk('local')->path($path);
            $connection = config('database.default');
            $dbConnection = config("database.connections.{$connection}");

            // Validasi backup file exists
            if (! file_exists($backupPath)) {
                throw new \Exception('File backup tidak ditemukan.');
            }

            // Extract backup ZIP
            $tempDir = storage_path('restore_temp_'.time());
            @mkdir($tempDir);

            $zip = new ZipArchive();
            if ($zip->open($backupPath) !== true) {
                throw new \Exception('Gagal membuka file backup.');
            }

            $zip->extractTo($tempDir);
            $zip->close();

            // Cari dump file di dalam ZIP
            $dumpFile = $this->findDumpFile($tempDir);
            if (! $dumpFile) {
                throw new \Exception('File database dump tidak ditemukan dalam backup.');
            }

            // Restore sesuai jenis database
            if ($connection === 'mysql') {
                $this->restoreMysql($dumpFile, $dbConnection);
            } elseif ($connection === 'sqlite') {
                $this->restoreSqlite($dumpFile, $dbConnection);
            } else {
                throw new \Exception("Database connection '{$connection}' tidak didukung untuk restore.");
            }

            // Cleanup temp directory
            $this->deleteDirectory($tempDir);

            $this->message = 'Database berhasil di-restore dari backup.';
            $this->error = null;
        } catch (\Throwable $exception) {
            $this->error = 'Restore gagal: '.$exception->getMessage();
            $this->message = null;
            
            // Cleanup on error
            if (isset($tempDir) && is_dir($tempDir)) {
                $this->deleteDirectory($tempDir);
            }
        }
    }

    public function uploadAndRestore(): void
    {
        abort_unless(Gate::allows('accessBackups', Backup::class), 403);

        $this->validate();

        if (! $this->uploadedBackupFile) {
            $this->error = 'Pilih file backup terlebih dahulu.';
            return;
        }

        try {
            $connection = config('database.default');
            $dbConnection = config("database.connections.{$connection}");

            // Simpan file upload ke temp
            $tempFile = $this->uploadedBackupFile->store('backup_uploads', 'local');
            $backupPath = Storage::disk('local')->path($tempFile);

            // Extract backup ZIP
            $tempDir = storage_path('restore_temp_'.time());
            @mkdir($tempDir);

            $zip = new ZipArchive();
            if ($zip->open($backupPath) !== true) {
                throw new \Exception('Gagal membuka file backup.');
            }

            $zip->extractTo($tempDir);
            $zip->close();

            // Cari dump file di dalam ZIP
            $dumpFile = $this->findDumpFile($tempDir);
            if (! $dumpFile) {
                throw new \Exception('File database dump tidak ditemukan dalam backup.');
            }

            // Restore sesuai jenis database
            if ($connection === 'mysql') {
                $this->restoreMysql($dumpFile, $dbConnection);
            } elseif ($connection === 'sqlite') {
                $this->restoreSqlite($dumpFile, $dbConnection);
            } else {
                throw new \Exception("Database connection '{$connection}' tidak didukung untuk restore.");
            }

            // Cleanup temp files
            $this->deleteDirectory($tempDir);
            Storage::disk('local')->delete($tempFile);

            $this->uploadedBackupFile = null;
            $this->message = 'Database berhasil di-restore dari file backup.';
            $this->error = null;
        } catch (\Throwable $exception) {
            $this->error = 'Restore gagal: '.$exception->getMessage();
            $this->message = null;
            
            // Cleanup on error
            if (isset($tempDir) && is_dir($tempDir)) {
                $this->deleteDirectory($tempDir);
            }
            if (isset($tempFile)) {
                Storage::disk('local')->delete($tempFile);
            }
        }
    }

    protected function restoreMysql(string $dumpFile, array $config): void
    {
        $host = $config['host'] ?? 'localhost';
        $user = $config['username'] ?? '';
        $password = $config['password'] ?? '';
        $database = $config['database'] ?? '';
        $port = $config['port'] ?? 3306;

        // Build mysql command
        $command = sprintf(
            'mysql -h %s -P %d -u %s %s < %s',
            escapeshellarg($host),
            $port,
            escapeshellarg($user),
            $password ? '-p'.escapeshellarg($password) : '',
            escapeshellarg($dumpFile)
        );

        $output = null;
        $returnCode = null;
        exec($command, $output, $returnCode);

        if ($returnCode !== 0) {
            throw new \Exception('MySQL restore failed: '.implode(' ', $output));
        }
    }

    protected function restoreSqlite(string $dumpFile, array $config): void
    {
        $dbPath = $config['database'];

        // Handle gzipped SQL dump
        $sqlFile = $dumpFile;
        if (str_ends_with($dumpFile, '.sql.gz')) {
            $sqlFile = str_replace('.sql.gz', '.sql', $dumpFile);
            if (! file_exists($sqlFile)) {
                if (! function_exists('gzopen')) {
                    throw new \Exception('gzip extension not available for decompression.');
                }
                
                $gzFile = gzopen($dumpFile, 'rb');
                $sqlContent = gzread($gzFile, filesize($dumpFile) * 5);
                gzclose($gzFile);
                file_put_contents($sqlFile, $sqlContent);
            }
        }

        // Read SQL dump content
        if (! file_exists($sqlFile)) {
            throw new \Exception('SQL dump file not found: '.$sqlFile);
        }

        $sql = file_get_contents($sqlFile);
        if (! $sql) {
            throw new \Exception('SQL dump file is empty.');
        }

        // Create backup of current database (don't delete, just clear)
        try {
            $pdo = new \PDO('sqlite:'.$dbPath);
            $pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);

            // Disable foreign keys during restore
            $pdo->exec('PRAGMA foreign_keys = OFF;');

            // Get all table names
            $tables = $pdo->query("SELECT name FROM sqlite_master WHERE type='table' AND name NOT LIKE 'sqlite_%';")->fetchAll(\PDO::FETCH_COLUMN);

            // Drop all tables
            foreach ($tables as $table) {
                try {
                    $pdo->exec('DROP TABLE IF EXISTS `'.$table.'`;');
                } catch (\PDOException $e) {
                    // Skip errors on dropping tables
                }
            }

            // Split SQL by semicolon and execute each statement
            $statements = array_filter(
                array_map('trim', explode(';', $sql)),
                fn ($stmt) => ! empty($stmt)
            );

            foreach ($statements as $statement) {
                if (! empty($statement)) {
                    try {
                        $pdo->exec($statement);
                    } catch (\PDOException $e) {
                        // Skip some non-critical errors (like duplicate pragmas)
                        if (str_contains($e->getMessage(), 'PRAGMA') || str_contains($e->getMessage(), 'already exists')) {
                            continue;
                        }
                        throw $e;
                    }
                }
            }

            // Re-enable foreign keys
            $pdo->exec('PRAGMA foreign_keys = ON;');

            // Verify database integrity
            $result = $pdo->query('PRAGMA integrity_check;')->fetchColumn();
            if ($result !== 'ok') {
                throw new \Exception('Database integrity check failed: '.$result);
            }

            $pdo = null;
        } catch (\PDOException $e) {
            throw new \Exception('SQLite restore failed: '.$e->getMessage());
        }

        // Cleanup decompressed file
        if (str_ends_with($dumpFile, '.sql.gz') && file_exists($sqlFile) && $sqlFile !== $dumpFile) {
            unlink($sqlFile);
        }
    }

    protected function findDumpFile(string $directory): ?string
    {
        $files = glob($directory.'/**/*', 16); // GLOB_RECURSIVE = 16

        foreach ($files as $file) {
            if (is_file($file) && (str_ends_with($file, '.sql') || str_ends_with($file, '.sql.gz'))) {
                return $file;
            }
        }

        return null;
    }

    protected function deleteDirectory(string $directory): bool
    {
        if (! is_dir($directory)) {
            return false;
        }

        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($directory, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );

        foreach ($files as $fileinfo) {
            if ($fileinfo->isDir()) {
                rmdir($fileinfo->getRealPath());
            } else {
                unlink($fileinfo->getRealPath());
            }
        }

        return rmdir($directory);
    }

    protected function isValidBackupPath(string $path): bool
    {
        $folder = config('backup.backup.name');

        return str_starts_with($path, $folder.'/')
            && str_ends_with($path, '.zip')
            && ! str_contains($path, '..');
    }

    public function render()
    {
        return view('livewire.backups.index');
    }
}

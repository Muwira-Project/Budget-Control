<?php

namespace App\Livewire\Backups;

use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Spatie\Backup\Helpers\Format;
use Symfony\Component\HttpFoundation\StreamedResponse;

#[Layout('layouts.app')]
class Index extends Component
{
    public ?string $message = null;

    public ?string $error = null;

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

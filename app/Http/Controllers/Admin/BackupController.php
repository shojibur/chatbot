<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class BackupController extends Controller
{
    public function index(): Response
    {
        $backups = $this->getBackups();

        return Inertia::render('backups/Index', [
            'backups' => $backups,
        ]);
    }

    public function run(): RedirectResponse
    {
        Artisan::call('backup:run --only-db --disable-notifications');

        return back()->with('status', 'backup-started');
    }

    public function destroy(string $filename): RedirectResponse
    {
        $path = config('app.name').'/'.$filename;

        if (Storage::disk('local')->exists($path)) {
            Storage::disk('local')->delete($path);
        }

        return back()->with('status', 'backup-deleted');
    }

    private function getBackups(): array
    {
        $disk = Storage::disk('local');
        $appName = config('app.name');

        if (! $disk->exists($appName)) {
            return [];
        }

        return collect($disk->files($appName))
            ->filter(fn (string $f) => str_ends_with($f, '.zip'))
            ->map(function (string $path) use ($disk) {
                $filename = basename($path);
                $size = $disk->size($path);
                $lastModified = $disk->lastModified($path);

                return [
                    'filename' => $filename,
                    'size' => $this->formatBytes($size),
                    'size_bytes' => $size,
                    'created_at' => date('Y-m-d H:i:s', $lastModified),
                ];
            })
            ->sortByDesc('size_bytes')
            ->values()
            ->toArray();
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes >= 1073741824) {
            return round($bytes / 1073741824, 2).' GB';
        }
        if ($bytes >= 1048576) {
            return round($bytes / 1048576, 2).' MB';
        }

        return round($bytes / 1024, 2).' KB';
    }
}

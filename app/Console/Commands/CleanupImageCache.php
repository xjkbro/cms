<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class CleanupImageCache extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'images:cleanup {--days=30 : Number of days to keep cached images}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Clean up old cached resized images';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $days = (int) $this->option('days');
        $cacheDir = 'cache/images';

        if (!Storage::disk('public')->exists($cacheDir)) {
            $this->info('No image cache directory found.');
            return 0;
        }

        $files = Storage::disk('public')->files($cacheDir);
        $cutoff = now()->subDays($days)->timestamp;
        $deletedCount = 0;
        $totalSize = 0;

        foreach ($files as $file) {
            $lastModified = Storage::disk('public')->lastModified($file);
            if ($lastModified < $cutoff) {
                $size = Storage::disk('public')->size($file);
                $totalSize += $size;
                Storage::disk('public')->delete($file);
                $deletedCount++;
            }
        }

        if ($deletedCount > 0) {
            $this->info("Deleted {$deletedCount} cached images older than {$days} days.");
            $this->info("Freed up " . $this->formatBytes($totalSize) . " of disk space.");
        } else {
            $this->info("No cached images older than {$days} days found.");
        }

        return 0;
    }

    private function formatBytes($bytes, $precision = 2)
    {
        $units = array('B', 'KB', 'MB', 'GB', 'TB');

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }
}

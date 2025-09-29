<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    protected $fillable = [
        'user_id',
        'project_id',
        'name',
        'file_name',
        'mime_type',
        'path',
        'disk',
        'file_hash',
        'collection',
        'size',
    ];

    protected $appends = ['url', 'human_readable_size'];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function getUrlAttribute(): string
    {
        if ($this->disk === 'url') {
            return $this->path;
        }

        // Use the images route which can handle both resized and original images
        return url('/images/' . $this->path);
    }

    public function getResizedUrl(?int $width = null, ?int $height = null, string $fit = 'contain'): string
    {
        if ($this->disk === 'url') {
            return $this->path;
        }

        $url = url('/images/' . $this->path);
        $params = [];

        if ($width) $params['w'] = $width;
        if ($height) $params['h'] = $height;
        if ($fit !== 'contain') $params['fit'] = $fit;

        return $url . (empty($params) ? '' : '?' . http_build_query($params));
    }

    /**
     * Get a simple resized URL - equivalent to asset(Storage::url($path)) but with resizing
     */
    public static function imageUrl(string $path, ?int $width = null, ?int $height = null): string
    {
        $url = url('/images/' . $path);
        $params = [];

        if ($width) $params['w'] = $width;
        if ($height) $params['h'] = $height;

        return $url . (empty($params) ? '' : '?' . http_build_query($params));
    }

    public function getHumanReadableSizeAttribute(): string
    {
        $bytes = $this->size;
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, 2) . ' ' . $units[$i];
    }
}

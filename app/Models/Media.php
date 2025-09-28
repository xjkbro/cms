<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Media extends Model
{
    protected $fillable = [
        'user_id',
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

    public function getUrlAttribute(): string
    {
        if ($this->disk === 'url') {
            return $this->path;
        }

        return asset(Storage::url($this->path));
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

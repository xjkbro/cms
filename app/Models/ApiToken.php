<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ApiToken extends Model
{
    protected $fillable = [
        'user_id',
        'name',
        'token',
        'display_token',
        'abilities',
        'last_used_at',
        'expires_at',
    ];

    protected $casts = [
        'abilities' => 'array',
        'last_used_at' => 'datetime',
        'expires_at' => 'datetime',
    ];

    protected $hidden = [
        'token',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        return !$this->isExpired();
    }

    public function hasAbility(string $ability): bool
    {
        return in_array('*', $this->abilities ?? []) || in_array($ability, $this->abilities ?? []);
    }

    public static function generateToken(): string
    {
        return Str::random(80);
    }

    public function markAsUsed(): void
    {
        $this->update(['last_used_at' => now()]);
    }

    // Create display version of plaintext token
    public static function createDisplayToken(string $plainTextToken): string
    {
        return substr($plainTextToken, 0, 8) . '...' . substr($plainTextToken, -8);
    }
}

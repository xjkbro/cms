<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'name',
        'description',
        'slug',
        'user_id',
        'is_active',
        'is_default',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_default' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }

    public function media(): HasMany
    {
        return $this->hasMany(Media::class);
    }

    public function publishedPosts(): HasMany
    {
        return $this->hasMany(Post::class)->where('is_draft', false);
    }

    public function draftPosts(): HasMany
    {
        return $this->hasMany(Post::class)->where('is_draft', true);
    }

    public static function defaultForUser($userId): ?Project
    {
        return self::where('user_id', $userId)
            ->where('is_default', true)
            ->first();
    }

    public function makeDefault(): void
    {
        // Remove default from other projects
        self::where('user_id', $this->user_id)
            ->where('id', '!=', $this->id)
            ->update(['is_default' => false]);

        // Set this as default
        $this->update(['is_default' => true]);
    }

    // Collaboration relationships
    public function collaborators()
    {
        return $this->belongsToMany(User::class, 'project_users')
            ->withPivot(['role', 'joined_at'])
            ->withTimestamps();
    }

    public function invitations()
    {
        return $this->hasMany(ProjectInvitation::class);
    }

    // Helper methods for permissions
    public function hasUser(User $user): bool
    {
        return $this->user_id === $user->id || $this->collaborators->contains($user->id);
    }

    public function getUserRole(User $user): ?string
    {
        if ($this->user_id === $user->id) {
            return 'owner';
        }

        $collaborator = $this->collaborators->find($user->id);
        return $collaborator ? $collaborator->pivot->role : null;
    }

    public function canUserEdit(User $user): bool
    {
        $role = $this->getUserRole($user);
        return in_array($role, ['owner', 'admin', 'editor']);
    }

    public function canUserAdmin(User $user): bool
    {
        $role = $this->getUserRole($user);
        return in_array($role, ['owner', 'admin']);
    }

    public function canUserView(User $user): bool
    {
        return $this->hasUser($user);
    }
}

<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Fortify\TwoFactorAuthenticatable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, TwoFactorAuthenticatable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
    
    public function projects()
    {
        return $this->hasMany(Project::class);
    }
    
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
    
    public function categories()
    {
        return $this->hasMany(Category::class);
    }
    
    public function media()
    {
        return $this->hasMany(Media::class);
    }
    
    public function apiTokens()
    {
        return $this->hasMany(ApiToken::class);
    }

    // Collaboration relationships
    public function collaboratingProjects()
    {
        return $this->belongsToMany(Project::class, 'project_users')
            ->withPivot(['role', 'joined_at'])
            ->withTimestamps();
    }

    public function allProjects()
    {
        return $this->projects()->union($this->collaboratingProjects()->getQuery());
    }

    public function projectInvitations()
    {
        return $this->hasMany(ProjectInvitation::class, 'invited_by');
    }

    public function receivedInvitations()
    {
        return ProjectInvitation::where('email', $this->email);
    }

    // Multi-author post relationships
    public function authoredPosts()
    {
        return $this->belongsToMany(Post::class, 'post_authors')
            ->withPivot(['contribution_type', 'order'])
            ->withTimestamps();
    }
}

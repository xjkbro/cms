<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Post extends Model
{
    //
    protected $fillable = [
        'user_id',
        'project_id',
        'category_id',
        'title',
        'slug',
        'excerpt',
        'content',
        'tags',
        'is_draft',
    ];

    protected $casts = [
        'is_draft' => 'boolean',
    ];



    protected static function booted()
    {
        static::creating(function ($post) {
            if (empty($post->slug)) {
                $post->slug = (string) \Illuminate\Support\Str::uuid();
            }
        });
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function project()
    {
        return $this->belongsTo(Project::class);
    }
    
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
}

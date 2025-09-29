<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;

class Category extends Model
{
    //

    protected $fillable = [
        'user_id',
        'project_id',
        'name',
        'slug',
        'description',
    ];



    public function user()
    {
        return $this->belongsTo(User::class);
    }
    
    public function project()
    {
        return $this->belongsTo(Project::class);
    }
    
    public function posts()
    {
        return $this->hasMany(Post::class);
    }
}

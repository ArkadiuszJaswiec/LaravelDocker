<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Project extends Model
{
    use HasFactory;

    protected $table = 'projects';

    public function tasks()
    {
        return $this->hasMany(Task::class, 'project_id');
    }

    public function team()
    {
        return $this->hasMany(Team::class, 'project_id');
    }
    public function owner()
    {
        return $this->belongsTo(User::class, 'owner');
    }
}

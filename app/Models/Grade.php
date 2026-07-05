<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Grade extends Model
{
    protected $fillable = ['name', 'level', 'order', 'is_active'];

    public function teachers()
    {
        return $this->belongsToMany(Teacher::class, 'grade_teacher');
    }

    public function classes()
    {
        return $this->hasMany(ClassRoom::class, 'grade_id');
    }

    public function students()
    {
        return $this->hasMany(Student::class, 'grade_id');
    }
}
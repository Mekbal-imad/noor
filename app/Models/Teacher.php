<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Teacher extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name', 'email', 'password',
        'phone', 'gender', 'specialization',
        'role', 'photo', 'is_active'
    ];

    protected $hidden = ['password', 'remember_token'];

    public function grades()
    {
        return $this->belongsToMany(Grade::class, 'grade_teacher');
    }

   public function classes()
{
    return $this->belongsToMany(ClassRoom::class, 'class_teacher', 'teacher_id', 'class_id');
}

    public function memorizationRecords()
    {
        return $this->hasMany(MemorizationRecord::class, 'teacher_id');
    }

    public function studyPlans()
    {
        return $this->hasMany(StudyPlan::class, 'teacher_id');
    }
}
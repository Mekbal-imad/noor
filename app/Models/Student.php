<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Student extends Model
{
   protected $fillable = [
    'parent_id', 'grade_id', 'name', 'gender',
    'birth_date', 'grade_level', 'status', 'photo',
    'health_condition'
];

    public function parent()
    {
        return $this->belongsTo(ParentModel::class, 'parent_id');
    }

    public function grade()
    {
        return $this->belongsTo(Grade::class, 'grade_id');
    }

    public function classes()
{
    return $this->belongsToMany(ClassRoom::class, 'student_class', 'student_id', 'class_id');
}
    public function attendance()
    {
        return $this->hasMany(Attendance::class, 'student_id');
    }

    public function memorizationRecords()
    {
        return $this->hasMany(MemorizationRecord::class, 'student_id');
    }

    public function studyPlans()
    {
        return $this->hasMany(StudyPlan::class, 'student_id');
    }
}
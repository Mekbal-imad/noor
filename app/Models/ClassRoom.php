<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ClassRoom extends Model
{
    protected $table = 'classes';

    protected $fillable = [
        'grade_id', 'name', 'type',
        'time_type', 'prayer_time',
        'start_time', 'end_time',
        'days', 'is_active'
    ];

    public function grade()
    {
        return $this->belongsTo(Grade::class, 'grade_id');
    }

   public function teachers()
{
    return $this->belongsToMany(Teacher::class, 'class_teacher', 'class_id', 'teacher_id');
}

public function students()
{
    return $this->belongsToMany(Student::class, 'student_class', 'class_id', 'student_id');
}
    public function attendance()
    {
        return $this->hasMany(Attendance::class, 'class_id');
    }

    public function exams()
    {
        return $this->hasMany(Exam::class, 'class_id');
    }
}
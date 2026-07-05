<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MemorizationRecord extends Model
{
    protected $fillable = [
        'student_id', 'teacher_id', 'type',
        'from_surah', 'from_ayah',
        'to_surah', 'to_ayah',
        'grade', 'notes', 'date'
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }
}
<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    protected $fillable = [
        'class_id', 'title',
        'description', 'exam_date'
    ];

    public function class()
    {
        return $this->belongsTo(ClassRoom::class, 'class_id');
    }
}
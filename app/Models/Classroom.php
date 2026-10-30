<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Classroom extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function students()
    {
        return $this->hasMany(Student::class);
    }

    public function teacherSchedules()
    {
        return $this->hasMany(TeacherSchedule::class);
    }

    public function waliKelas()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }
}

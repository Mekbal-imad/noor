<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\ClassRoom;
use App\Models\Student;
use Illuminate\Http\Request;

class GradeController extends Controller
{
    public function index()
    {
        $grades = Grade::with(['teachers', 'classes.teachers'])
            ->withCount(['students', 'classes'])
            ->orderBy('order')
            ->get();

        return response()->json($grades);
    }

    public function store(Request $request)
    {
        $request->validate([
            'name'       => 'required|string',
            'level'      => 'required|in:primary,middle,high',
            'order'      => 'nullable|integer',
            'teacher_ids' => 'nullable|array|max:2',
            'teacher_ids.*' => 'exists:teachers,id',
        ]);

        $grade = Grade::create([
            'name'      => $request->name,
            'level'     => $request->level,
            'order'     => $request->order ?? 0,
            'is_active' => true,
        ]);

        // Assign up to 2 teachers to the grade
        if ($request->teacher_ids) {
            $grade->teachers()->sync($request->teacher_ids);
        }

        return response()->json([
            'message' => 'تم إنشاء المرحلة بنجاح',
            'grade'   => $grade->load('teachers')
        ], 201);
    }

    public function show($id)
{
    $grade = Grade::with([
        'teachers',
        'classes.teachers',
        'students.parent',
    ])
    ->withCount(['students', 'classes'])
    ->findOrFail($id);

    $grade->classes->loadCount('students');

    return response()->json($grade);
}
    public function update(Request $request, $id)
    {
        $grade = Grade::findOrFail($id);

        $request->validate([
            'name'        => 'sometimes|string',
            'level'       => 'sometimes|in:primary,middle,high',
            'order'       => 'nullable|integer',
            'is_active'   => 'sometimes|boolean',
            'teacher_ids' => 'nullable|array|max:2',
            'teacher_ids.*' => 'exists:teachers,id',
        ]);

        $grade->update($request->only('name', 'level', 'order', 'is_active'));

        if ($request->has('teacher_ids')) {
            $grade->teachers()->sync($request->teacher_ids);
        }

        return response()->json([
            'message' => 'تم تحديث المرحلة بنجاح',
            'grade'   => $grade->load('teachers')
        ]);
    }

    public function destroy($id)
    {
        Grade::findOrFail($id)->delete();
        return response()->json(['message' => 'تم حذف المرحلة بنجاح']);
    }

    // Add a class to a grade
    public function storeClass(Request $request, $gradeId)
    {
        $request->validate([
            'name'        => 'required|string',
            'type'        => 'required|string',
            'time_type'   => 'required|in:prayer,specific',
            'prayer_time' => 'nullable|in:asr,maghrib,isha',
            'start_time'  => 'nullable|date_format:H:i',
            'end_time'    => 'nullable|date_format:H:i',
            'days'        => 'nullable|string',
            'teacher_ids' => 'nullable|array',
            'teacher_ids.*' => 'exists:teachers,id',
        ]);

        $grade = Grade::findOrFail($gradeId);

        $class = ClassRoom::create([
            'grade_id'    => $grade->id,
            'name'        => $request->name,
            'type'        => $request->type,
            'time_type'   => $request->time_type,
            'prayer_time' => $request->prayer_time,
            'start_time'  => $request->start_time,
            'end_time'    => $request->end_time,
            'days'        => $request->days,
            'is_active'   => true,
        ]);

        // Assign teachers to this class
        if ($request->teacher_ids) {
            $class->teachers()->sync($request->teacher_ids);
        }

        // Auto-enroll all students of this grade
        $studentIds = Student::where('grade_id', $gradeId)
            ->where('status', 'approved')
            ->pluck('id');

        $class->students()->sync($studentIds);

        return response()->json([
            'message' => 'تم إنشاء الحصة بنجاح وتسجيل الطلاب تلقائياً',
            'class'   => $class->load('teachers')
        ], 201);
    }

    // Get all classes of a grade
    public function getClasses($gradeId)
    {
        $classes = ClassRoom::with(['teachers'])
            ->withCount('students')
            ->where('grade_id', $gradeId)
            ->get();

        return response()->json($classes);
    }

    // Get all students of a grade
    public function getStudents($gradeId)
    {
        $students = Student::with('parent')
            ->where('grade_id', $gradeId)
            ->get();

        return response()->json($students);
    }

    // Assign student to grade
    public function assignStudent(Request $request, $gradeId)
    {
        $request->validate([
            'student_id' => 'required|exists:students,id'
        ]);

        $student = Student::findOrFail($request->student_id);
        $student->update(['grade_id' => $gradeId]);

        // Auto-enroll in all classes of this grade
        $classIds = ClassRoom::where('grade_id', $gradeId)->pluck('id');
        $student->classes()->syncWithoutDetaching($classIds);

        return response()->json(['message' => 'تم إضافة الطالب للمرحلة بنجاح']);
    }
}
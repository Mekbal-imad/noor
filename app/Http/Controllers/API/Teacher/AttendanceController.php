<?php

namespace App\Http\Controllers\API\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\NoorNotification;
use App\Models\Student;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index(Request $request)
{
    $teacherClassIds = $request->user()->classes()->pluck('classes.id');

    $query = Attendance::with('student')
        ->whereIn('class_id', $teacherClassIds);

    if ($request->class_id) {
        $query->where('class_id', $request->class_id);
    }

    if ($request->date) {
        $query->whereDate('date', $request->date);
    } else {
        $query->orderBy('date', 'desc');
    }

    return response()->json($query->get());
}

    public function store(Request $request)
    {
        $request->validate([
            'class_id'   => 'required|exists:classes,id',
            'date'       => 'required|date',
            'attendance' => 'required|array',
            'attendance.*.student_id' => 'required|exists:students,id',
            'attendance.*.status'     => 'required|in:present,absent,late',
            'attendance.*.notes'      => 'nullable|string',
        ]);

        foreach ($request->attendance as $record) {
            Attendance::updateOrCreate(
                [
                    'student_id' => $record['student_id'],
                    'class_id'   => $request->class_id,
                    'date'       => $request->date,
                ],
                [
                    'status' => $record['status'],
                    'notes'  => $record['notes'] ?? null,
                ]
            );

            // Notify parent if absent
            if ($record['status'] === 'absent') {
                $student = Student::with('parent')->find($record['student_id']);
                if ($student && $student->parent) {
                    NoorNotification::create([
                        'notifiable_id'   => $student->parent->id,
                        'notifiable_type' => 'App\Models\ParentModel',
                        'title'           => 'غياب الطالب',
                        'body'            => "غاب {$student->name} عن الحصة اليوم",
                        'type'            => 'attendance',
                    ]);
                }
            }
        }

        return response()->json(['message' => 'تم تسجيل الحضور بنجاح']);
    }

    public function classStudents($classId)
   {
    $class = \App\Models\ClassRoom::with('students')->findOrFail($classId);
    return response()->json($class->students);
   }
    public function markTeacherAttendance(Request $request)
{
    $request->validate([
        'class_id'      => 'required|exists:classes,id',
        'date'          => 'required|date',
        'status'        => 'required|in:present,absent',
        'justification' => 'required_if:status,absent|nullable|string',
    ]);

    $record = \App\Models\TeacherAttendance::updateOrCreate(
        [
            'class_id'   => $request->class_id,
            'teacher_id' => $request->user()->id,
            'date'       => $request->date,
        ],
        [
            'status'        => $request->status,
            'justification' => $request->justification,
            'class_held'    => $request->status === 'present',
        ]
    );

    return response()->json([
        'message' => $request->status === 'present'
            ? 'تم تسجيل حضورك، يمكنك الآن تسجيل حضور الطلاب'
            : 'تم تسجيل غيابك، سيتم تسجيل الحصة كغير منعقدة',
        'record' => $record,
    ]);
}

public function getTeacherAttendance(Request $request)
{
    $request->validate([
        'class_id' => 'required|exists:classes,id',
        'date'     => 'required|date',
    ]);

    $record = \App\Models\TeacherAttendance::where('class_id', $request->class_id)
        ->where('teacher_id', $request->user()->id)
        ->where('date', $request->date)
        ->first();

    return response()->json($record);
}

}
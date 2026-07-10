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

public function gradeStats(Request $request)
{
    $teacher = $request->user();
    $period  = $request->period ?? 'week';

    $startDate = $period === 'week'
        ? \Carbon\Carbon::now()->startOfWeek()
        : \Carbon\Carbon::now()->startOfMonth();

    // Get grades this teacher teaches
    $gradeIds = $teacher->grades()->pluck('grades.id');
    $grades   = \App\Models\Grade::with(['students.parent'])
        ->whereIn('id', $gradeIds)
        ->get();

    $result = [];

    foreach ($grades as $grade) {
        $classIds   = $grade->classes()->pluck('classes.id');
        $studentIds = $grade->students->pluck('id');

        $totalRecords = \App\Models\Attendance::whereIn('student_id', $studentIds)
            ->whereIn('class_id', $classIds)
            ->where('date', '>=', $startDate)
            ->count();

        $presentCount = \App\Models\Attendance::whereIn('student_id', $studentIds)
            ->whereIn('class_id', $classIds)
            ->where('date', '>=', $startDate)
            ->where('status', 'present')
            ->count();

        $absentCount = \App\Models\Attendance::whereIn('student_id', $studentIds)
            ->whereIn('class_id', $classIds)
            ->where('date', '>=', $startDate)
            ->where('status', 'absent')
            ->count();

        $lateCount = \App\Models\Attendance::whereIn('student_id', $studentIds)
            ->whereIn('class_id', $classIds)
            ->where('date', '>=', $startDate)
            ->where('status', 'late')
            ->count();

        $attendanceRate = $totalRecords > 0
            ? round(($presentCount / $totalRecords) * 100, 1)
            : 0;

        $avgMemGrade = \App\Models\MemorizationRecord::whereIn('student_id', $studentIds)
            ->where('teacher_id', $teacher->id)
            ->where('date', '>=', $startDate)
            ->avg('grade') ?? 0;

        $classesHeld = \App\Models\TeacherAttendance::whereIn('class_id', $classIds)
            ->where('date', '>=', $startDate)
            ->where('class_held', true)
            ->count();

        $classesMissed = \App\Models\TeacherAttendance::whereIn('class_id', $classIds)
            ->where('date', '>=', $startDate)
            ->where('class_held', false)
            ->count();

        $result[] = [
            'grade'          => ['id' => $grade->id, 'name' => $grade->name],
            'total_students' => $grade->students->count(),
            'attendance'     => [
                'rate'    => $attendanceRate,
                'present' => $presentCount,
                'absent'  => $absentCount,
                'late'    => $lateCount,
                'total'   => $totalRecords,
            ],
            'memorization'   => [
                'avg_grade' => round($avgMemGrade, 1),
            ],
            'classes'        => [
                'held'   => $classesHeld,
                'missed' => $classesMissed,
            ],
        ];
    }

    return response()->json($result);
}

public function studentStats(Request $request, $studentId)
{
    $teacher   = $request->user();
    $period    = $request->period ?? 'week';

    $startDate = $period === 'week'
        ? \Carbon\Carbon::now()->startOfWeek()
        : \Carbon\Carbon::now()->startOfMonth();

    $student = \App\Models\Student::with('parent')->findOrFail($studentId);

    $classIds = $teacher->classes()->pluck('classes.id');

    // Attendance
    $attendance = \App\Models\Attendance::where('student_id', $studentId)
        ->whereIn('class_id', $classIds)
        ->where('date', '>=', $startDate)
        ->get();

    $totalRecords  = $attendance->count();
    $presentCount  = $attendance->where('status', 'present')->count();
    $absentCount   = $attendance->where('status', 'absent')->count();
    $lateCount     = $attendance->where('status', 'late')->count();
    $attendanceRate = $totalRecords > 0
        ? round(($presentCount / $totalRecords) * 100, 1)
        : 0;

    // Memorization
    $memRecords = \App\Models\MemorizationRecord::where('student_id', $studentId)
        ->where('teacher_id', $teacher->id)
        ->where('date', '>=', $startDate)
        ->orderBy('date', 'desc')
        ->get();

    $avgGrade      = round($memRecords->avg('grade') ?? 0, 1);
    $lastRecord    = $memRecords->first();

    // Recent attendance details
    $recentAttendance = \App\Models\Attendance::where('student_id', $studentId)
        ->whereIn('class_id', $classIds)
        ->where('date', '>=', $startDate)
        ->orderBy('date', 'desc')
        ->with('class')
        ->get();

    return response()->json([
        'student' => [
            'id'               => $student->id,
            'name'             => $student->name,
            'health_condition' => $student->health_condition,
            'parent_name'      => $student->parent?->name,
            'parent_phone'     => $student->parent?->phone,
            'parent_email'     => $student->parent?->email,
        ],
        'attendance' => [
            'rate'    => $attendanceRate,
            'present' => $presentCount,
            'absent'  => $absentCount,
            'late'    => $lateCount,
            'total'   => $totalRecords,
            'recent'  => $recentAttendance->map(fn($a) => [
                'date'       => $a->date,
                'status'     => $a->status,
                'class_name' => $a->class?->name,
            ]),
        ],
        'memorization' => [
            'avg_grade'    => $avgGrade,
            'total'        => $memRecords->count(),
            'memorization' => $memRecords->where('type', 'memorization')->count(),
            'revision'     => $memRecords->where('type', 'revision')->count(),
            'confirmation' => $memRecords->where('type', 'confirmation')->count(),
            'last_record'  => $lastRecord ? [
                'type'       => $lastRecord->type,
                'from_surah' => $lastRecord->from_surah,
                'from_ayah'  => $lastRecord->from_ayah,
                'to_surah'   => $lastRecord->to_surah,
                'to_ayah'    => $lastRecord->to_ayah,
                'grade'      => $lastRecord->grade,
                'date'       => $lastRecord->date,
            ] : null,
            'records' => $memRecords->map(fn($r) => [
                'date'       => $r->date,
                'type'       => $r->type,
                'from_surah' => $r->from_surah,
                'from_ayah'  => $r->from_ayah,
                'to_surah'   => $r->to_surah,
                'to_ayah'    => $r->to_ayah,
                'grade'      => $r->grade,
                'notes'      => $r->notes,
            ]),
        ],
    ]);
}

}
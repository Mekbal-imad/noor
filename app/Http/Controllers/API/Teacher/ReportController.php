<?php

namespace App\Http\Controllers\API\Teacher;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\ClassRoom;
use App\Models\MemorizationRecord;
use App\Models\Student;
use App\Models\StudyPlan;
use App\Models\TeacherAttendance;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    private function assertTeacherOwnsClass(Request $request, int $classId): ClassRoom
    {
        $class = $request->user()
            ->classes()
            ->with('grade')
            ->where('classes.id', $classId)
            ->first();

        if (!$class) {
            abort(403, 'ليس لديك صلاحية للوصول إلى هذه الحلقة');
        }

        return $class;
    }

    public function daily(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'date'     => 'required|date',
        ]);

        $class = $this->assertTeacherOwnsClass($request, (int) $request->class_id);
        $students = $class->students()->orderBy('name')->get();
        $studentIds = $students->pluck('id');

        $attendance = Attendance::where('class_id', $class->id)
            ->whereDate('date', $request->date)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->keyBy('student_id');

        $memRecords = MemorizationRecord::where('teacher_id', $request->user()->id)
            ->whereDate('date', $request->date)
            ->whereIn('student_id', $studentIds)
            ->get()
            ->groupBy('student_id');

        $rows = $students->map(function ($student) use ($attendance, $memRecords) {
            $att = $attendance->get($student->id);
            $lessons = ($memRecords->get($student->id) ?? collect())->values()->map(fn ($r) => [
                'id'         => $r->id,
                'type'       => $r->type,
                'from_surah' => $r->from_surah,
                'from_ayah'  => $r->from_ayah,
                'to_surah'   => $r->to_surah,
                'to_ayah'    => $r->to_ayah,
                'grade'      => $r->grade,
                'notes'      => $r->notes,
            ]);

            return [
                'student' => [
                    'id'   => $student->id,
                    'name' => $student->name,
                ],
                'attendance' => $att ? [
                    'status' => $att->status,
                    'notes'  => $att->notes,
                ] : null,
                'lessons' => $lessons,
                'lesson_counts' => [
                    'memorization' => $lessons->where('type', 'memorization')->count(),
                    'revision'     => $lessons->where('type', 'revision')->count(),
                    'confirmation' => $lessons->where('type', 'confirmation')->count(),
                ],
            ];
        });

        return response()->json([
            'class' => [
                'id'         => $class->id,
                'name'       => $class->name,
                'grade_name' => $class->grade?->name,
            ],
            'date' => $request->date,
            'summary' => [
                'students'     => $students->count(),
                'present'      => $attendance->where('status', 'present')->count(),
                'absent'       => $attendance->where('status', 'absent')->count(),
                'late'         => $attendance->where('status', 'late')->count(),
                'memorization' => $memRecords->flatten(1)->where('type', 'memorization')->count(),
                'revision'     => $memRecords->flatten(1)->where('type', 'revision')->count(),
                'confirmation' => $memRecords->flatten(1)->where('type', 'confirmation')->count(),
            ],
            'rows' => $rows,
        ]);
    }

    public function attendance(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'from'     => 'required|date',
            'to'       => 'required|date|after_or_equal:from',
        ]);

        $class = $this->assertTeacherOwnsClass($request, (int) $request->class_id);
        $students = $class->students()->orderBy('name')->get();
        $studentIds = $students->pluck('id');

        $records = Attendance::where('class_id', $class->id)
            ->whereIn('student_id', $studentIds)
            ->whereBetween('date', [$request->from, $request->to])
            ->get()
            ->groupBy('student_id');

        $rows = $students->map(function ($student) use ($records) {
            $list = $records->get($student->id) ?? collect();
            $present = $list->where('status', 'present')->count();
            $absent  = $list->where('status', 'absent')->count();
            $late    = $list->where('status', 'late')->count();
            $total   = $list->count();
            $rate    = $total > 0 ? round(($present / $total) * 100, 1) : 0;

            return [
                'student' => [
                    'id'   => $student->id,
                    'name' => $student->name,
                ],
                'present' => $present,
                'absent'  => $absent,
                'late'    => $late,
                'total'   => $total,
                'rate'    => $rate,
            ];
        });

        $all = $records->flatten(1);
        $presentCount = $all->where('status', 'present')->count();
        $absentCount  = $all->where('status', 'absent')->count();
        $lateCount    = $all->where('status', 'late')->count();
        $totalCount   = $all->count();
        $totalStudents = $students->count();
        $sessionCount = $all->pluck('date')->unique()->count();
        $avgPresent = $sessionCount > 0
            ? round(($presentCount + $lateCount) / $sessionCount, 1)
            : 0;

        $classesHeld = TeacherAttendance::where('class_id', $class->id)
            ->whereBetween('date', [$request->from, $request->to])
            ->where('class_held', true)
            ->count();

        $classesMissed = TeacherAttendance::where('class_id', $class->id)
            ->whereBetween('date', [$request->from, $request->to])
            ->where('class_held', false)
            ->count();

        return response()->json([
            'class' => [
                'id'         => $class->id,
                'name'       => $class->name,
                'grade_name' => $class->grade?->name,
            ],
            'from' => $request->from,
            'to'   => $request->to,
            'summary' => [
                'present'        => $presentCount,
                'absent'         => $absentCount,
                'late'           => $lateCount,
                'total'          => $totalCount,
                'rate'           => $totalCount > 0
                    ? round(($presentCount / $totalCount) * 100, 1)
                    : 0,
                'avg_present'    => $avgPresent,
                'total_students' => $totalStudents,
                'sessions'       => $sessionCount,
                'classes_held'   => $classesHeld,
                'classes_missed' => $classesMissed,
            ],
            'rows' => $rows,
        ]);
    }

    public function student(Request $request, $id)
    {
        $request->validate([
            'from' => 'required|date',
            'to'   => 'required|date|after_or_equal:from',
        ]);

        $teacher = $request->user();
        $classIds = $teacher->classes()->pluck('classes.id');

        $student = Student::with('parent')->findOrFail($id);

        $inTeacherClass = $student->classes()
            ->whereIn('classes.id', $classIds)
            ->exists();

        if (!$inTeacherClass) {
            abort(403, 'ليس لديك صلاحية للوصول إلى بيانات هذا الطالب');
        }

        $attendance = Attendance::where('student_id', $student->id)
            ->whereIn('class_id', $classIds)
            ->whereBetween('date', [$request->from, $request->to])
            ->with('class')
            ->orderBy('date', 'desc')
            ->get();

        $present = $attendance->where('status', 'present')->count();
        $absent  = $attendance->where('status', 'absent')->count();
        $late    = $attendance->where('status', 'late')->count();
        $total   = $attendance->count();
        $rate    = $total > 0 ? round(($present / $total) * 100, 1) : 0;

        $memRecords = MemorizationRecord::where('student_id', $student->id)
            ->where('teacher_id', $teacher->id)
            ->whereBetween('date', [$request->from, $request->to])
            ->orderBy('date', 'desc')
            ->get();

        $avgGrade = round($memRecords->avg('grade') ?? 0, 1);
        $lastRecord = $memRecords->first();

        $studyPlans = StudyPlan::where('student_id', $student->id)
            ->where('teacher_id', $teacher->id)
            ->orderBy('target_date', 'desc')
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
            'from' => $request->from,
            'to'   => $request->to,
            'attendance' => [
                'rate'    => $rate,
                'present' => $present,
                'absent'  => $absent,
                'late'    => $late,
                'total'   => $total,
                'recent'  => $attendance->map(fn ($a) => [
                    'date'       => $a->date,
                    'status'     => $a->status,
                    'class_name' => $a->class?->name,
                    'notes'      => $a->notes,
                ]),
            ],
            'discipline' => [
                'rate'            => $rate,
                'punctuality_rate'=> $total > 0
                    ? round((($present + $late) / $total) * 100, 1)
                    : 0,
                'absent_rate'     => $total > 0 ? round(($absent / $total) * 100, 1) : 0,
                'late_rate'       => $total > 0 ? round(($late / $total) * 100, 1) : 0,
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
                'records' => $memRecords->map(fn ($r) => [
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
            'study_plans' => $studyPlans->map(fn ($p) => [
                'id'          => $p->id,
                'title'       => $p->title,
                'description' => $p->description,
                'target_date' => $p->target_date,
                'status'      => $p->status,
            ]),
        ]);
    }

    public function session(Request $request)
    {
        $request->validate([
            'class_id' => 'required|exists:classes,id',
            'from'     => 'required|date',
            'to'       => 'required|date|after_or_equal:from',
        ]);

        $teacher = $request->user();
        $class = $this->assertTeacherOwnsClass($request, (int) $request->class_id);
        $students = $class->students()->orderBy('name')->get();
        $studentIds = $students->pluck('id');

        $attendance = Attendance::where('class_id', $class->id)
            ->whereIn('student_id', $studentIds)
            ->whereBetween('date', [$request->from, $request->to])
            ->get();

        $memRecords = MemorizationRecord::where('teacher_id', $teacher->id)
            ->whereIn('student_id', $studentIds)
            ->whereBetween('date', [$request->from, $request->to])
            ->get();

        $teacherAtt = TeacherAttendance::where('teacher_id', $teacher->id)
            ->where('class_id', $class->id)
            ->whereBetween('date', [$request->from, $request->to])
            ->get();

        $heldDays = $teacherAtt->where('class_held', true)->count();
        $missedDays = $teacherAtt->where('class_held', false)->count();
        $attendanceDays = $attendance->pluck('date')->unique()->count();

        $present = $attendance->where('status', 'present')->count();
        $absent  = $attendance->where('status', 'absent')->count();
        $late    = $attendance->where('status', 'late')->count();
        $attTotal = $attendance->count();

        $byStudent = $students->map(function ($student) use ($attendance, $memRecords) {
            $att = $attendance->where('student_id', $student->id);
            $mem = $memRecords->where('student_id', $student->id);
            $total = $att->count();
            $presentCount = $att->where('status', 'present')->count();

            return [
                'student' => [
                    'id'   => $student->id,
                    'name' => $student->name,
                ],
                'attendance' => [
                    'present' => $presentCount,
                    'absent'  => $att->where('status', 'absent')->count(),
                    'late'    => $att->where('status', 'late')->count(),
                    'total'   => $total,
                    'rate'    => $total > 0 ? round(($presentCount / $total) * 100, 1) : 0,
                ],
                'lessons' => [
                    'total'        => $mem->count(),
                    'memorization' => $mem->where('type', 'memorization')->count(),
                    'revision'     => $mem->where('type', 'revision')->count(),
                    'confirmation' => $mem->where('type', 'confirmation')->count(),
                    'avg_grade'    => round($mem->avg('grade') ?? 0, 1),
                ],
            ];
        });

        return response()->json([
            'class' => [
                'id'         => $class->id,
                'name'       => $class->name,
                'grade_name' => $class->grade?->name,
                'type'       => $class->type,
            ],
            'from' => $request->from,
            'to'   => $request->to,
            'summary' => [
                'students'        => $students->count(),
                'attendance_days' => $attendanceDays,
                'classes_held'    => $heldDays,
                'classes_missed'  => $missedDays,
                'attendance' => [
                    'present' => $present,
                    'absent'  => $absent,
                    'late'    => $late,
                    'total'   => $attTotal,
                    'rate'    => $attTotal > 0 ? round(($present / $attTotal) * 100, 1) : 0,
                ],
                'lessons' => [
                    'total'        => $memRecords->count(),
                    'memorization' => $memRecords->where('type', 'memorization')->count(),
                    'revision'     => $memRecords->where('type', 'revision')->count(),
                    'confirmation' => $memRecords->where('type', 'confirmation')->count(),
                    'avg_grade'    => round($memRecords->avg('grade') ?? 0, 1),
                ],
            ],
            'students' => $byStudent,
        ]);
    }
}

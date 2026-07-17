<?php

namespace App\Http\Controllers\API\Admin;

use App\Http\Controllers\Controller;
use App\Models\Grade;
use App\Models\Attendance;
use App\Models\MemorizationRecord;
use App\Models\TeacherAttendance;
use App\Models\ClassRoom;
use Illuminate\Http\Request;
use Carbon\Carbon;

class StatsController extends Controller
{
    // Overview stats for all grades
    public function overview()
    {
        $grades = Grade::withCount(['students', 'classes'])->get();

        $totalStudents   = $grades->sum('students_count');
        $totalClasses    = $grades->sum('classes_count');
        $totalGrades     = $grades->count();

        $today           = Carbon::today();
        $attendanceToday = Attendance::whereDate('date', $today)->count();
        $absentToday     = Attendance::whereDate('date', $today)->where('status', 'absent')->count();
        $classesHeldToday = TeacherAttendance::whereDate('date', $today)->where('class_held', true)->count();
        $classesMissedToday = TeacherAttendance::whereDate('date', $today)->where('class_held', false)->count();

        return response()->json([
            'total_grades'          => $totalGrades,
            'total_students'        => $totalStudents,
            'total_classes'         => $totalClasses,
            'attendance_today'      => $attendanceToday,
            'absent_today'          => $absentToday,
            'classes_held_today'    => $classesHeldToday,
            'classes_missed_today'  => $classesMissedToday,
            'grades'                => $grades,
        ]);
    }

    // Detailed stats for a specific grade
    public function gradeStats(Request $request, $gradeId)
    {
        $request->validate([
            'period' => 'nullable|in:week,month,year',
        ]);

        $period = $request->period ?? 'month';
        $grade  = Grade::with(['classes', 'students'])->findOrFail($gradeId);

        $startDate = match($period) {
            'week'  => Carbon::now()->startOfWeek(),
            'month' => Carbon::now()->startOfMonth(),
            'year'  => Carbon::now()->startOfYear(),
        };

        $studentIds = $grade->students->pluck('id');
        $classIds   = $grade->classes->pluck('id');

        // ── Attendance Stats ──
        $totalAttendanceRecords = Attendance::whereIn('student_id', $studentIds)
            ->whereIn('class_id', $classIds)
            ->where('date', '>=', $startDate)
            ->count();

        $presentCount = Attendance::whereIn('student_id', $studentIds)
            ->whereIn('class_id', $classIds)
            ->where('date', '>=', $startDate)
            ->where('status', 'present')
            ->count();

        $absentCount = Attendance::whereIn('student_id', $studentIds)
            ->whereIn('class_id', $classIds)
            ->where('date', '>=', $startDate)
            ->where('status', 'absent')
            ->count();

        $lateCount = Attendance::whereIn('student_id', $studentIds)
            ->whereIn('class_id', $classIds)
            ->where('date', '>=', $startDate)
            ->where('status', 'late')
            ->count();

        $attendanceRate = $totalAttendanceRecords > 0
            ? round(($presentCount / $totalAttendanceRecords) * 100, 1)
            : 0;

        // ── Daily Attendance (last 7 days) ──
        $dailyAttendance = [];
        for ($i = 6; $i >= 0; $i--) {
            $date  = Carbon::now()->subDays($i);
            $total = Attendance::whereIn('student_id', $studentIds)
                ->whereIn('class_id', $classIds)
                ->whereDate('date', $date)
                ->count();
            $present = Attendance::whereIn('student_id', $studentIds)
                ->whereIn('class_id', $classIds)
                ->whereDate('date', $date)
                ->where('status', 'present')
                ->count();

            $dailyAttendance[] = [
                'date'    => $date->format('Y-m-d'),
                'label'   => $date->locale('ar')->dayName,
                'total'   => $total,
                'present' => $present,
                'rate'    => $total > 0 ? round(($present / $total) * 100) : 0,
            ];
        }

        // ── Classes Held vs Missed ──
        $classesHeld = TeacherAttendance::whereIn('class_id', $classIds)
            ->where('date', '>=', $startDate)
            ->where('class_held', true)
            ->count();

        $classesMissed = TeacherAttendance::whereIn('class_id', $classIds)
            ->where('date', '>=', $startDate)
            ->where('class_held', false)
            ->count();

        // ── Memorization Stats ──
        $memorizationRecords = MemorizationRecord::whereIn('student_id', $studentIds)
            ->where('date', '>=', $startDate)
            ->get();

        $avgGrade = $memorizationRecords->avg('grade') ?? 0;
        $totalMemorization = $memorizationRecords->where('type', 'memorization')->count();
        $totalRevision     = $memorizationRecords->where('type', 'revision')->count();
        $totalConfirmation = $memorizationRecords->where('type', 'confirmation')->count();

        // ── Top Students (by attendance) ──
        $studentStats = [];
        foreach ($grade->students as $student) {
            $total = Attendance::where('student_id', $student->id)
                ->whereIn('class_id', $classIds)
                ->where('date', '>=', $startDate)
                ->count();
            $present = Attendance::where('student_id', $student->id)
                ->whereIn('class_id', $classIds)
                ->where('date', '>=', $startDate)
                ->where('status', 'present')
                ->count();
            $rate = $total > 0 ? round(($present / $total) * 100) : 0;

            $avgMem = MemorizationRecord::where('student_id', $student->id)
                ->where('date', '>=', $startDate)
                ->avg('grade') ?? 0;

            $lastMemorization = MemorizationRecord::where('student_id', $student->id)
    ->where('type', 'memorization')
    ->orderBy('date', 'desc')
    ->first();


$memorizationCount = MemorizationRecord::where('student_id', $student->id)
    ->where('type', 'memorization')
    ->where('date', '>=', $startDate)
    ->count();

$studentStats[] = [
    'id'                => $student->id,
    'name'              => $student->name,
    'attendance_rate'   => $rate,
    'avg_grade'         => round($avgMem, 1),
    'total_sessions'    => $total,
    'memorization_count' => $memorizationCount,
    'last_memorization' => $lastMemorization ? [
        'from_surah' => $lastMemorization->from_surah,
        'from_ayah'  => $lastMemorization->from_ayah,
        'to_surah'   => $lastMemorization->to_surah,
        'to_ayah'    => $lastMemorization->to_ayah,
        'grade'      => $lastMemorization->grade,
        'date'       => $lastMemorization->date,
    ] : null,
];
        }

        usort($studentStats, fn($a, $b) => $b['attendance_rate'] - $a['attendance_rate']);

        return response()->json([
            'grade'            => $grade,
            'period'           => $period,
            'attendance' => [
                'rate'    => $attendanceRate,
                'present' => $presentCount,
                'absent'  => $absentCount,
                'late'    => $lateCount,
                'total'   => $totalAttendanceRecords,
                'daily'   => $dailyAttendance,
            ],
            'classes' => [
                'held'   => $classesHeld,
                'missed' => $classesMissed,
                'total'  => $classesHeld + $classesMissed,
            ],
            'memorization' => [
                'avg_grade'    => round($avgGrade, 1),
                'memorization' => $totalMemorization,
                'revision'     => $totalRevision,
                'confirmation' => $totalConfirmation,
                'total'        => $memorizationRecords->count(),
            ],
            'students' => $studentStats,
        ]);
    }
}

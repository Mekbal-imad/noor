<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\AuthController;
use App\Http\Controllers\API\MessageController;
use App\Http\Controllers\API\Admin\TeacherController;
use App\Http\Controllers\API\Admin\StudentController;
use App\Http\Controllers\API\Admin\ClassController;
use App\Http\Controllers\API\Admin\ExamController;
use App\Http\Controllers\API\Teacher\AttendanceController;
use App\Http\Controllers\API\Teacher\MemorizationController;
use App\Http\Controllers\API\Teacher\StudyPlanController;
use App\Http\Controllers\API\Parent\ChildController;
use App\Http\Controllers\API\Parent\NotificationController;
use App\Http\Controllers\API\Admin\GradeController;
use App\Http\Controllers\API\Admin\StatsController;

// ─── Public Routes ───────────────────────────────────────
Route::post('/login', [AuthController::class, 'login']);

// ─── Protected Routes ────────────────────────────────────
Route::middleware('auth:sanctum')->group(function () {

    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',      [AuthController::class, 'me']);

    // Messages (all roles)
    Route::get('/messages',  [MessageController::class, 'index']);
    Route::post('/messages', [MessageController::class, 'store']);

    // ── Admin Routes ──────────────────────────────────────
    Route::prefix('admin')->group(function () {

        // Teachers
        Route::apiResource('teachers', TeacherController::class);

        // Students
        Route::get('students',          [StudentController::class, 'index']);
        Route::get('students/pending',  [StudentController::class, 'pending']);
        Route::get('students/{id}',     [StudentController::class, 'show']);
        Route::post('students/{id}/approve', [StudentController::class, 'approve']);
        Route::post('students/{id}/reject',  [StudentController::class, 'reject']);
        Route::delete('students/{id}',  [StudentController::class, 'destroy']);
        Route::post('students',        [StudentController::class, 'store']);
        Route::post('students/import', [StudentController::class, 'import']);

        // Classes
        Route::apiResource('classes', ClassController::class);
        Route::post('classes/{id}/assign-student', [ClassController::class, 'assignStudent']);
        Route::delete('classes/{id}', [ClassController::class, 'destroy']);

        // Exams
        Route::apiResource('exams', ExamController::class);

        // Grades
         Route::get('grades',                          [GradeController::class, 'index']);
         Route::post('grades',                         [GradeController::class, 'store']);
         Route::get('grades/{id}',                     [GradeController::class, 'show']);
         Route::put('grades/{id}',                     [GradeController::class, 'update']);
         Route::delete('grades/{id}',                  [GradeController::class, 'destroy']);
         Route::get('grades/{id}/classes',             [GradeController::class, 'getClasses']);
         Route::post('grades/{id}/classes',            [GradeController::class, 'storeClass']);
         Route::get('grades/{id}/students',            [GradeController::class, 'getStudents']);
         Route::post('grades/{id}/assign-student',     [GradeController::class, 'assignStudent']);
    
         // stats
         Route::get('stats/overview',        [StatsController::class, 'overview']);
         Route::get('stats/grades/{id}',     [StatsController::class, 'gradeStats']);
    });

    // ── Teacher Routes ────────────────────────────────────
    Route::prefix('teacher')->group(function () {
        Route::get('my-classes', [AuthController::class, 'myClasses']);
        Route::get('classes/{id}/students', [AttendanceController::class, 'classStudents']);
        Route::post('teacher-attendance', [AttendanceController::class, 'markTeacherAttendance']);
        Route::get('teacher-attendance',  [AttendanceController::class, 'getTeacherAttendance']);
        Route::put('profile/password', [AuthController::class, 'changePassword']);

        // Attendance
        Route::get('attendance',  [AttendanceController::class, 'index']);
        Route::post('attendance', [AttendanceController::class, 'store']);

        // Memorization
        Route::get('memorization',                        [MemorizationController::class, 'index']);
        Route::post('memorization',                       [MemorizationController::class, 'store']);
        Route::get('memorization/student/{studentId}',   [MemorizationController::class, 'studentHistory']);
        Route::delete('memorization/{id}',               [MemorizationController::class, 'destroy']);

        // Study Plans
        Route::get('study-plans',       [StudyPlanController::class, 'index']);
        Route::post('study-plans',      [StudyPlanController::class, 'store']);
        Route::put('study-plans/{id}',  [StudyPlanController::class, 'update']);
        Route::delete('study-plans/{id}', [StudyPlanController::class, 'destroy']);
    });

    // ── Parent Routes ─────────────────────────────────────
    Route::prefix('parent')->group(function () {

        // Children
        Route::get('children',        [ChildController::class, 'index']);
        Route::get('children/{id}',   [ChildController::class, 'show']);
        Route::post('children',       [ChildController::class, 'store']);

        // Notifications
        Route::get('notifications',           [NotificationController::class, 'index']);
        Route::post('notifications/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::post('notifications/read-all', [NotificationController::class, 'markAllAsRead']);
    });
});
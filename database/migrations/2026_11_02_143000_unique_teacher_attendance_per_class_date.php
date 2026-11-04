<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Keep the earliest row per class+date; drop later duplicates (co-teacher double saves)
        DB::statement('
            DELETE t1 FROM teacher_attendance t1
            INNER JOIN teacher_attendance t2
                ON t1.class_id = t2.class_id
                AND t1.date = t2.date
                AND t1.id > t2.id
        ');

        Schema::table('teacher_attendance', function (Blueprint $table) {
            $table->unique(['class_id', 'date'], 'teacher_attendance_class_date_unique');
        });
    }

    public function down(): void
    {
        Schema::table('teacher_attendance', function (Blueprint $table) {
            $table->dropUnique('teacher_attendance_class_date_unique');
        });
    }
};

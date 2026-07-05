<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            // Remove old teacher_id and type columns
            $table->dropForeign(['teacher_id']);
            $table->dropColumn(['teacher_id', 'type', 'grade_level', 'start_time', 'end_time', 'days']);

            // Add new columns
            $table->foreignId('grade_id')->after('id')->constrained('grades')->onDelete('cascade');
            $table->string('type')->after('name');
            $table->enum('time_type', ['prayer', 'specific'])->default('prayer')->after('type');
            $table->enum('prayer_time', ['asr', 'maghrib', 'isha'])->nullable()->after('time_type');
            $table->time('start_time')->nullable()->after('prayer_time');
            $table->time('end_time')->nullable()->after('start_time');
            $table->string('days')->nullable()->after('end_time');
        });
    }

    public function down(): void
    {
        Schema::table('classes', function (Blueprint $table) {
            $table->dropForeign(['grade_id']);
            $table->dropColumn(['grade_id', 'type', 'time_type', 'prayer_time', 'start_time', 'end_time', 'days']);
        });
    }
};
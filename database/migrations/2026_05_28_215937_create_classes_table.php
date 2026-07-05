<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('classes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->string('name');
            $table->enum('type', ['quran', 'sira', 'adab']);
            $table->enum('grade_level', [
                'primary_3', 'primary_4', 'primary_5', 'primary_6',
                'middle_1', 'middle_2', 'middle_3',
                'high_1', 'high_2', 'high_3'
            ]);
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classes');
    }
};
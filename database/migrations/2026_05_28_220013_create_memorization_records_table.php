<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('memorization_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignId('teacher_id')->constrained('teachers')->onDelete('cascade');
            $table->enum('type', ['memorization', 'revision', 'confirmation']);
            $table->string('from_surah');
            $table->integer('from_ayah');
            $table->string('to_surah');
            $table->integer('to_ayah');
            $table->decimal('grade', 4, 1)->nullable();
            $table->string('notes')->nullable();
            $table->date('date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('memorization_records');
    }
};
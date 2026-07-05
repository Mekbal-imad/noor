<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->constrained('parents')->onDelete('cascade');
            $table->string('name');
            $table->enum('gender', ['male', 'female']);
            $table->date('birth_date')->nullable();
            $table->enum('grade_level', [
                'primary_3', 'primary_4', 'primary_5', 'primary_6',
                'middle_1', 'middle_2', 'middle_3',
                'high_1', 'high_2', 'high_3'
            ]);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->string('photo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
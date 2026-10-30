<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
        });

        DB::statement('ALTER TABLE students MODIFY parent_id BIGINT UNSIGNED NULL');

        Schema::table('students', function (Blueprint $table) {
            $table->foreign('parent_id')
                ->references('id')
                ->on('parents')
                ->onDelete('cascade');
        });
    }

    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
        });

        DB::statement('ALTER TABLE students MODIFY parent_id BIGINT UNSIGNED NOT NULL');

        Schema::table('students', function (Blueprint $table) {
            $table->foreign('parent_id')
                ->references('id')
                ->on('parents')
                ->onDelete('cascade');
        });
    }
};

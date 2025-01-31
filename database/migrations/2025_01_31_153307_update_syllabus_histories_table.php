<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('syllabus_histories', function (Blueprint $table) {
            $table->string('name')->before('subject');
            $table->string('nip')->nullable()->change();
            $table->foreign('user_id')
                  ->references('id')
                  ->on('users')
                  ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('syllabus_histories', function (Blueprint $table) {
            $table->dropForeign(['user_id']);
            $table->string('nip')->nullable(false)->change();
            $table->dropColumn('name');
        });
    }
};

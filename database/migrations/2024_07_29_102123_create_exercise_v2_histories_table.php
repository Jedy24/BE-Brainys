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
        Schema::create('exercise_v2_histories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phase');
            $table->string('subject');
            $table->string('element');
            $table->unsignedInteger('number_of_question');
            $table->enum('type', ['essay', 'multiple_choice']);
            $table->text('notes');
            $table->json('output_data');
            $table->foreignId('user_id')->constrained('users', 'id')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exercise_v2_histories');
    }
};

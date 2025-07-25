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
        Schema::create('rubrik_nilai_histories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phase');
            $table->string('subject');
            $table->string('element');
            $table->longText('notes');
            $table->json('generate_output');
            $table->foreignId('user_id')->constrained('users', 'id')->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('rubrik_nilai_histories');
    }
};

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
        Schema::create('alur_tujuan_pembelajaran_histories', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('phase');
            $table->string('subject');
            $table->string('element');
            $table->integer('weeks');
            $table->string('notes');
            $table->json('output_data');
            $table->timestamps();
            
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('alur_tujuan_pembelajaran_histories');
    }
};

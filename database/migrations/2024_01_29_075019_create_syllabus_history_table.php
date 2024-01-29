<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSyllabusHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('syllabus_history', function (Blueprint $table) {
            $table->id();
            $table->string('subject');
            $table->string('class');
            $table->longText('notes'); // Mengubah tipe data menjadi longText
            $table->json('output_data'); // Menambah kolom output_data dengan tipe data JSON
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('syllabus_history');
    }
}

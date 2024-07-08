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
        Schema::table('hint_histories', function (Blueprint $table) {
            $table->string('pokok_materi')->after('name');
            $table->string('elemen_capaian')->after('grade');
            $table->integer('jumlah_soal')->after('elemen_capaian');
            $table->string('notes')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hint_histories', function (Blueprint $table) {
            $table->dropColumn('pokok_materi');
            $table->dropColumn('elemen_capaian');
            $table->dropColumn('jumlah_soal');
            $table->longText('notes')->nullable(false)->change();
        });
    }
};

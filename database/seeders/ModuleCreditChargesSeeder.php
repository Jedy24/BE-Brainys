<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ModuleCreditChargesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::table('module_credit_charges')->insert([
            [
                'id' => 1,
                'name' => 'Bahan Ajar',
                'slug' => 'bahan-ajar',
                'credit_charged_generate' => 1,
                'credit_charged_docx' => 0,
                'credit_charged_pptx' => 0,
                'credit_charged_xlsx' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 2,
                'name' => 'Silabus',
                'slug' => 'silabus',
                'credit_charged_generate' => 1,
                'credit_charged_docx' => 0,
                'credit_charged_pptx' => 0,
                'credit_charged_xlsx' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 3,
                'name' => 'Soal',
                'slug' => 'soal',
                'credit_charged_generate' => 1,
                'credit_charged_docx' => 0,
                'credit_charged_pptx' => 0,
                'credit_charged_xlsx' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 4,
                'name' => 'Modul Ajar',
                'slug' => 'modul-ajar',
                'credit_charged_generate' => 1,
                'credit_charged_docx' => 0,
                'credit_charged_pptx' => 0,
                'credit_charged_xlsx' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 5,
                'name' => 'Gamifikasi',
                'slug' => 'gamifikasi',
                'credit_charged_generate' => 1,
                'credit_charged_docx' => 0,
                'credit_charged_pptx' => 0,
                'credit_charged_xlsx' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 6,
                'name' => 'Kisi-Kisi Soal',
                'slug' => 'kisi-kisi-soal',
                'credit_charged_generate' => 1,
                'credit_charged_docx' => 0,
                'credit_charged_pptx' => 0,
                'credit_charged_xlsx' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'id' => 7,
                'name' => 'Alur Tujuan Pembelajaran',
                'slug' => 'alur-tujuan-pembelajaran',
                'credit_charged_generate' => 1,
                'credit_charged_docx' => 0,
                'credit_charged_pptx' => 0,
                'credit_charged_xlsx' => 0,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ]);
    }
}

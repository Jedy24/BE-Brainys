<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ModulesStats extends BaseWidget
{
    protected static ?int $sort = 2;

    protected function getStats(): array
    {
        $totalMaterialHistory       = (User::withCount('materialHistory')->get()->sum('material_history_count')) + (User::withCount('bahanAjarHistory')->get()->sum('bahan_ajar_history_count'));
        $totalSyllabusHistory       = User::withCount('syllabusHistory')->get()->sum('syllabus_history_count');
        $totalExerciseHistory       = (User::withCount('exerciseHistory')->get()->sum('exercise_history_count')) + (User::withCount('exerciseV2History')->get()->sum('exercise_v2_history_count'));
        $totalBahanAjarHistory      = User::withCount('bahanAjarHistory')->get()->sum('bahan_ajar_history_count');
        $totalGamificationHistory   = User::withCount('gamificationHistory')->get()->sum('gamification_history_count');
        $totalHintHistory           = User::withCount('hintHistory')->get()->sum('hint_history_count');
        $totalATPHistory            = User::withCount('alurTujuanPembelajaranHistory')->get()->sum('alur_tujuan_pembelajaran_history_count');

        return [
            Stat::make('Riwayat Modul Ajar', $totalMaterialHistory),
            Stat::make('Riwayat Silabus', $totalSyllabusHistory),
            Stat::make('Riwayat Soal', $totalExerciseHistory),
            Stat::make('Riwayat Bahan Ajar', $totalBahanAjarHistory),
            Stat::make('Riwayat Gamifikasi', $totalGamificationHistory),
            Stat::make('Riwayat Kisi-Kisi', $totalHintHistory),
            Stat::make('Riwayat ATP', $totalATPHistory),
            Stat::make('Soon <3', 0),
            Stat::make('Soon <3', 0),
        ];
    }
}

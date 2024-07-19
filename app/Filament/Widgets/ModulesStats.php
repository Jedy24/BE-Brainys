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
        $totalMaterialHistory       = User::withCount('materialHistory')->get()->sum('material_history_count');
        $totalSyllabusHistory       = User::withCount('syllabusHistory')->get()->sum('syllabus_history_count');
        $totalExerciseHistory       = User::withCount('exerciseHistory')->get()->sum('exercise_history_count');
        $totalBahanAjarHistory      = User::withCount('bahanAjarHistory')->get()->sum('bahan_ajar_history_count');
        $totalGamificationHistory   = User::withCount('gamificationHistory')->get()->sum('gamification_history_count');
        $totalHintHistory           = User::withCount('hintHistory')->get()->sum('hint_history_count');
        $totalATPHistory            = User::withCount('alurTujuanPembelajaranHistory')->get()->sum('alur_tujuan_pembelajaran_history_count');

        return [
            Stat::make('Generated Syllabus', $totalSyllabusHistory),
            Stat::make('Generated Material', $totalMaterialHistory),
            Stat::make('Generated Exercise', $totalExerciseHistory),
            Stat::make('Generated Bahan Ajar', $totalBahanAjarHistory),
            Stat::make('Generated Gamification', $totalGamificationHistory),
            Stat::make('Generated Hint', $totalHintHistory),
            Stat::make('Generated ATP', $totalATPHistory),
            Stat::make('Soon <3', 0),
            Stat::make('Soon <3', 0),
        ];
    }
}

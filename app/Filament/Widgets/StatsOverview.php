<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $totalMaterialHistory = User::withCount('materialHistory')->get()->sum('material_history_count');
        $totalSyllabusHistory = User::withCount('syllabusHistory')->get()->sum('syllabus_history_count');
        $totalExerciseHistory = User::withCount('exerciseHistory')->get()->sum('exercise_history_count');

        return [
            Stat::make('Total Material History', $totalMaterialHistory),
            Stat::make('Total Syllabus History', $totalSyllabusHistory),
            Stat::make('Total Exercise History', $totalExerciseHistory),
        ];
    }
}

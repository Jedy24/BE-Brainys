<?php

namespace App\Filament\Widgets;

use App\Models\AlurTujuanPembelajaranHistories;
use App\Models\BahanAjarHistories;
use App\Models\ExerciseHistories;
use App\Models\ExerciseV2Histories;
use App\Models\GamificationHistories;
use App\Models\HintHistories;
use App\Models\MaterialHistories;
use App\Models\ModulAjarHistories;
use App\Models\SyllabusHistories;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class ModulesWidget extends ChartWidget
{
    protected static ?int $sort = 3;
    protected static ?string $heading = 'Generated Activity Last 7 Days';
    protected static ?string $maxHeight = '500px';
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $startDate = Carbon::now()->subDays(6);
        $endDate = Carbon::now();

        $aggregateData = function ($model) use ($startDate, $endDate) {
            return $model::whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->pluck('count', 'date');
        };

        $materialData = $aggregateData(MaterialHistories::class)->merge($aggregateData(ModulAjarHistories::class));
        $syllabusData = $aggregateData(SyllabusHistories::class);
        $exerciseData = $aggregateData(ExerciseHistories::class)->merge($aggregateData(ExerciseV2Histories::class));
        $bahanAjarData = $aggregateData(BahanAjarHistories::class);
        $gamificationData = $aggregateData(GamificationHistories::class);
        $hintData = $aggregateData(HintHistories::class);
        $ATPData = $aggregateData(AlurTujuanPembelajaranHistories::class);

        $dates = [];
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dates[$date->format('Y-m-d')] = 0;
        }

        $mergeData = fn($data) => array_values(array_replace($dates, $data->toArray()));

        // Format label dates to "18 Aug 2024"
        $formattedLabels = array_map(
            fn($date) => Carbon::parse($date)->format('d M Y'),
            array_keys($dates)
        );

        return [
            'datasets' => [
                [
                    'label' => 'Modul Ajar',
                    'data' => $mergeData($materialData),
                    'backgroundColor' => 'rgba(255, 99, 132, 0.5)',
                    'borderColor' => 'rgb(255, 99, 132)',
                    'fill' => false,
                ],
                [
                    'label' => 'Silabus',
                    'data' => $mergeData($syllabusData),
                    'backgroundColor' => 'rgba(54, 162, 235, 0.5)',
                    'borderColor' => 'rgb(54, 162, 235)',
                    'fill' => false,
                ],
                [
                    'label' => 'Soal',
                    'data' => $mergeData($exerciseData),
                    'backgroundColor' => 'rgba(75, 192, 192, 0.5)',
                    'borderColor' => 'rgb(75, 192, 192)',
                    'fill' => false,
                ],
                [
                    'label' => 'Bahan Ajar',
                    'data' => $mergeData($bahanAjarData),
                    'backgroundColor' => 'rgba(255, 205, 86, 0.5)',
                    'borderColor' => 'rgb(255, 205, 86)',
                    'fill' => false,
                ],
                [
                    'label' => 'Gamifikasi',
                    'data' => $mergeData($gamificationData),
                    'backgroundColor' => 'rgba(153, 102, 255, 0.5)',
                    'borderColor' => 'rgb(153, 102, 255)',
                    'fill' => false,
                ],
                [
                    'label' => 'Kisi-Kisi',
                    'data' => $mergeData($hintData),
                    'backgroundColor' => 'rgba(255, 159, 64, 0.5)',
                    'borderColor' => 'rgb(255, 159, 64)',
                    'fill' => false,
                ],
                [
                    'label' => 'ATP',
                    'data' => $mergeData($ATPData),
                    'backgroundColor' => 'rgba(75, 192, 192, 0.5)',
                    'borderColor' => 'rgb(75, 192, 192)',
                    'fill' => false,
                ],
            ],
            'labels' => $formattedLabels, // Menggunakan label yang sudah diformat
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }

    protected function getOptions(): array
    {
        return [
            'scales' => [
                'yAxes' => [
                    [
                        'ticks' => [
                            'beginAtZero' => true,
                            'stepSize' => 1,
                            'callback' => fn($value) => intval($value) == $value ? $value : null,
                        ],
                    ],
                ],
            ],
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'position' => 'top',
                ],
            ],
            'responsive' => true,
            'maintainAspectRatio' => true,
        ];
    }
}

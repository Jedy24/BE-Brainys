<?php

namespace App\Filament\Widgets;

use App\Models\AlurTujuanPembelajaranHistories;
use App\Models\BahanAjarHistories;
use App\Models\ExerciseHistories;
use App\Models\GamificationHistories;
use App\Models\HintHistories;
use App\Models\MaterialHistories;
use App\Models\SyllabusHistories;
use App\Models\User;
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

        // Helper function to aggregate data
        $aggregateData = function ($model) use ($startDate, $endDate) {
            return $model::whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->pluck('count', 'date');
        };

        $materialData = $aggregateData(MaterialHistories::class);
        $syllabusData = $aggregateData(SyllabusHistories::class);
        $exerciseData = $aggregateData(ExerciseHistories::class);
        $bahanAjarData = $aggregateData(BahanAjarHistories::class);
        $gamificationData = $aggregateData(GamificationHistories::class);
        $hintData = $aggregateData(HintHistories::class);
        $ATPData = $aggregateData(AlurTujuanPembelajaranHistories::class);

        // Generate all dates in the range
        $dates = [];
        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            $dates[$date->format('Y-m-d')] = 0;
        }

        // Merge and fill missing dates with zeros
        $mergeData = function ($data) use ($dates) {
            return array_values(array_replace($dates, $data->toArray()));
        };

        return [
            'datasets' => [
                [
                    'label' => 'Material Histories',
                    'data' => $mergeData($materialData),
                    'backgroundColor' => 'rgba(255, 99, 132, 0.5)', // Merah Muda Transparan
                    'borderColor' => 'rgb(255, 99, 132)', // Merah Muda
                    'fill' => false,
                ],
                [
                    'label' => 'Syllabus Histories',
                    'data' => $mergeData($syllabusData),
                    'backgroundColor' => 'rgba(54, 162, 235, 0.5)', // Biru Transparan
                    'borderColor' => 'rgb(54, 162, 235)', // Biru
                    'fill' => false,
                ],
                [
                    'label' => 'Exercise Histories',
                    'data' => $mergeData($exerciseData),
                    'backgroundColor' => 'rgba(75, 192, 192, 0.5)', // Aqua Transparan
                    'borderColor' => 'rgb(75, 192, 192)', // Aqua
                    'fill' => false,
                ],
                [
                    'label' => 'Bahan Ajar Histories',
                    'data' => $mergeData($bahanAjarData),
                    'backgroundColor' => 'rgba(255, 205, 86, 0.5)', // Kuning Transparan
                    'borderColor' => 'rgb(255, 205, 86)', // Kuning
                    'fill' => false,
                ],
                [
                    'label' => 'Gamification Histories',
                    'data' => $mergeData($gamificationData),
                    'backgroundColor' => 'rgba(153, 102, 255, 0.5)', // Ungu Transparan
                    'borderColor' => 'rgb(153, 102, 255)', // Ungu
                    'fill' => false,
                ],
                [
                    'label' => 'Hint Histories',
                    'data' => $mergeData($hintData),
                    'backgroundColor' => 'rgba(255, 159, 64, 0.5)', // Oranye Transparan
                    'borderColor' => 'rgb(255, 159, 64)', // Oranye
                    'fill' => false,
                ],
                [
                    'label' => 'ATP Histories',
                    'data' => $mergeData($ATPData),
                    'backgroundColor' => 'rgba(75, 192, 192, 0.5)', // Aqua Transparan
                    'borderColor' => 'rgb(75, 192, 192)', // Aqua
                    'fill' => false,
                ]
            ],
            'labels' => array_keys($dates),
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
                        'stepSize' => 1,  // Memaksa interval antar ticks menjadi bilangan bulat.
                        'callback' => function($value) {
                            return intval($value) == $value ? $value : null;  // Menghilangkan desimal
                        },
                        'suggestedMax' => 10, // Sesuaikan berdasarkan maksimal data yang Anda perkirakan.
                    ]
                ]
            ]
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

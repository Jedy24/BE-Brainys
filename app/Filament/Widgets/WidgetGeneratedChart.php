<?php

namespace App\Filament\Widgets;

use App\Models\BahanAjarHistories;
use App\Models\ExerciseHistories;
use App\Models\GamificationHistories;
use App\Models\MaterialHistories;
use App\Models\SyllabusHistories;
use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class WidgetGeneratedChart extends ChartWidget
{
    protected static ?string $heading = 'User Generated Activity Over the Last 7 Days';
    protected int | string | array $columnSpan = 'full';
    // protected static ?string $maxHeight = '500px';

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
                            // 'precision' => 0, // Menentukan bahwa tidak ada desimal pada ticks.
                            // 'suggestedMax' => 10, // Sesuaikan berdasarkan maksimal data yang Anda perkirakan.
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
            'maintainAspectRatio' => false,
        ];
    
    }
}

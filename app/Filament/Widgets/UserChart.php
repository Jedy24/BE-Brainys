<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class UserChart extends ChartWidget
{
    protected static ?int $sort = 4;

    protected static ?string $heading = 'User Activity Last 7 Days';
    
    protected static ?string $maxHeight = '500px';
    
    protected int | string | array $columnSpan = 'full';

    protected function getData(): array
    {
        $startDate = Carbon::now()->subDays(6)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        // Helper function to aggregate data
        $aggregateData = function ($isActive) use ($startDate, $endDate) {
            return User::where('is_active', $isActive)
                ->whereBetween('created_at', [$startDate, $endDate])
                ->selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->pluck('count', 'date');
        };

        $activeData = $aggregateData(true);
        $inactiveData = $aggregateData(false);

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
                    'label' => 'New Active Users',
                    'data' => $mergeData($activeData),
                    'backgroundColor' => 'rgba(75, 192, 192, 0.5)', // Aqua Transparan
                    'borderColor' => 'rgb(75, 192, 192)', // Aqua
                    'fill' => false,
                ],
                [
                    'label' => 'New Inactive Users',
                    'data' => $mergeData($inactiveData),
                    'backgroundColor' => 'rgba(255, 99, 132, 0.5)', // Merah Muda Transparan
                    'borderColor' => 'rgb(255, 99, 132)', // Merah Muda
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
                            'callback' => function ($value) {
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
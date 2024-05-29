<?php

namespace App\Filament\Widgets;

use App\Models\User;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;

class WidgetGeneratedChart extends ChartWidget
{
    protected static ?string $heading = 'Generated Module Chart';

    protected int | string | array $columnSpan = 'full';

    protected static ?string $maxHeight = '300px';

    protected function getData(): array
    {
        // Define the date range (last 25 days for example)
        $startDate = Carbon::now()->subDays(7);
        $endDate = Carbon::now();

        // Fetch the data
        $data = User::getGenerateAllSumGroupedByDate($startDate, $endDate);

        // Prepare the data for the chart
        $labels = $data->pluck('date')->toArray();
        $generatedData = $data->pluck('total')->toArray();

        return [
            'datasets' => [
                [
                    'label' => 'Modules Generated',
                    'data' => $generatedData,
                    'backgroundColor' => '#36A2EB',
                    'borderColor' => '#9BD0F5',
                ],
            ],
            'labels' => $labels,
        ];
    }

    protected function getType(): string
    {
        return 'line';
    }
}

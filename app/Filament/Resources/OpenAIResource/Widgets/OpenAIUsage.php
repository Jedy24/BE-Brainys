<?php

namespace App\Filament\Resources\OpenAIResource\Widgets;

use App\Services\OpenAIService;
use Exception;
use Filament\Notifications\Notification;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class OpenAIUsage extends BaseWidget
{
    protected function getStats(): array
    {
        try {
            $openAIService = app(OpenAIService::class);
            $credits = $openAIService->checkCredit();

            return [
                Stat::make('Total Credits Granted', $credits['total_granted']),
                Stat::make('Total Credits Used', $credits['total_used']),
                Stat::make('Total Credits Available', $credits['total_available']),
            ];
        } catch (Exception $e) {
            Notification::make()
                ->title('Failed to retrieve credit information: ' . $e->getMessage())
                ->danger()
                ->send();

            return [
                Stat::make('Total Credits Granted', 0),
                Stat::make('Total Credits Used', 0),
                Stat::make('Total Credits Available', 0),
            ];
        }
    }
}
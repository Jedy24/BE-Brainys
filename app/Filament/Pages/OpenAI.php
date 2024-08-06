<?php

namespace App\Filament\Pages;

use App\Filament\Resources\OpenAIResource\Widgets\OpenAIUsage;
use App\Filament\Widgets\StatsOverview;
use Filament\Pages\Page;

class OpenAI extends Page
{
    protected static string $view = 'filament.pages.open-a-i';

    protected static ?int $navigationSort = 14;

    protected static ?string $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Open AI';

    protected static ?string $navigationIcon = 'heroicon-c-command-line';
    
    protected ?string $heading = 'Open AI Monitor';

    public static function getSlug(): string
    {
        return 'open-ai';
    }

    public static function getLabel(): string
    {
        return 'Open AI';
    }

    public static function getPluralLabel(): string
    {
        return 'Open AI';
    }

    protected function getHeaderWidgets(): array
    {
        return [
            OpenAIUsage::class
        ];
    }
}

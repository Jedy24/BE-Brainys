<?php

namespace App\Filament\Resources\UserGamificationResource\Pages;

use App\Filament\Resources\UserGamificationResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUserGamifications extends ListRecords
{
    protected static string $resource = UserGamificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}

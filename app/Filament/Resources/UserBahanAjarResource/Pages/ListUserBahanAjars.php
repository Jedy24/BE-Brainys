<?php

namespace App\Filament\Resources\UserBahanAjarResource\Pages;

use App\Filament\Resources\UserBahanAjarResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUserBahanAjars extends ListRecords
{
    protected static string $resource = UserBahanAjarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}

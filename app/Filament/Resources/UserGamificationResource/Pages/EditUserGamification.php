<?php

namespace App\Filament\Resources\UserGamificationResource\Pages;

use App\Filament\Resources\UserGamificationResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUserGamification extends EditRecord
{
    protected static string $resource = UserGamificationResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\UserMaterialsResource\Pages;

use App\Filament\Resources\UserMaterialsResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUserMaterials extends ListRecords
{
    protected static string $resource = UserMaterialsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}

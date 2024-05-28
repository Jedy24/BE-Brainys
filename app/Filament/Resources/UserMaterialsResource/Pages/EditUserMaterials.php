<?php

namespace App\Filament\Resources\UserMaterialsResource\Pages;

use App\Filament\Resources\UserMaterialsResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUserMaterials extends EditRecord
{
    protected static string $resource = UserMaterialsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

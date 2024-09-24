<?php

namespace App\Filament\Resources\ModuleCreditChargeResource\Pages;

use App\Filament\Resources\ModuleCreditChargeResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListModuleCreditCharges extends ListRecords
{
    protected static string $resource = ModuleCreditChargeResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

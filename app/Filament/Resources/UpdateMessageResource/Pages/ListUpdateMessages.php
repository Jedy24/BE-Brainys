<?php

namespace App\Filament\Resources\UpdateMessageResource\Pages;

use App\Filament\Resources\UpdateMessageResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUpdateMessages extends ListRecords
{
    protected static string $resource = UpdateMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

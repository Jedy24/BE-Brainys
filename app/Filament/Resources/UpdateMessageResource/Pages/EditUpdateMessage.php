<?php

namespace App\Filament\Resources\UpdateMessageResource\Pages;

use App\Filament\Resources\UpdateMessageResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUpdateMessage extends EditRecord
{
    protected static string $resource = UpdateMessageResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

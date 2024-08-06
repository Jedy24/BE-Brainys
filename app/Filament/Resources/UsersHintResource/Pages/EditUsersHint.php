<?php

namespace App\Filament\Resources\UsersHintResource\Pages;

use App\Filament\Resources\UsersHintResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUsersHint extends EditRecord
{
    protected static string $resource = UsersHintResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

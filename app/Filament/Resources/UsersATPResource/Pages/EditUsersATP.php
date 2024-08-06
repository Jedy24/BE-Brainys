<?php

namespace App\Filament\Resources\UsersATPResource\Pages;

use App\Filament\Resources\UsersATPResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUsersATP extends EditRecord
{
    protected static string $resource = UsersATPResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\UsersATPResource\Pages;

use App\Filament\Resources\UsersATPResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUsersATPS extends ListRecords
{
    protected static string $resource = UsersATPResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

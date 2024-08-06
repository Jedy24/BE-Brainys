<?php

namespace App\Filament\Resources\UsersHintResource\Pages;

use App\Filament\Resources\UsersHintResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUsersHints extends ListRecords
{
    protected static string $resource = UsersHintResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

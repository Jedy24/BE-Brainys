<?php

namespace App\Filament\Resources\AutoInviteEmailResource\Pages;

use App\Filament\Resources\AutoInviteEmailResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAutoInviteEmails extends ListRecords
{
    protected static string $resource = AutoInviteEmailResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}

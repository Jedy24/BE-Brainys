<?php

namespace App\Filament\Resources\UserSyllabusResource\Pages;

use App\Filament\Resources\UserSyllabusResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUserSyllabus extends EditRecord
{
    protected static string $resource = UserSyllabusResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

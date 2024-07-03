<?php

namespace App\Filament\Resources\UserBahanAjarResource\Pages;

use App\Filament\Resources\UserBahanAjarResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUserBahanAjar extends EditRecord
{
    protected static string $resource = UserBahanAjarResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

<?php

namespace App\Filament\Resources\ExtraCreditResource\Pages;

use App\Filament\Resources\ExtraCreditResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditExtraCredit extends EditRecord
{
    protected static string $resource = ExtraCreditResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

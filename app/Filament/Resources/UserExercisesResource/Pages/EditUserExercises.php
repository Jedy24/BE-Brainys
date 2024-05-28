<?php

namespace App\Filament\Resources\UserExercisesResource\Pages;

use App\Filament\Resources\UserExercisesResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditUserExercises extends EditRecord
{
    protected static string $resource = UserExercisesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}

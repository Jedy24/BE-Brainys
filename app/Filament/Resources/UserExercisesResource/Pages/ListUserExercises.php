<?php

namespace App\Filament\Resources\UserExercisesResource\Pages;

use App\Filament\Resources\UserExercisesResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListUserExercises extends ListRecords
{
    protected static string $resource = UserExercisesResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Actions\CreateAction::make(),
        ];
    }
}

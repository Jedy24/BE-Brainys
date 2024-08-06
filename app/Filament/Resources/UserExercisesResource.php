<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserExercisesResource\Pages;
use App\Filament\Resources\UserExercisesResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserExercisesResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationGroup = 'Users Modules';

    protected static ?string $navigationLabel = 'Users Exercise';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getLabel(): string
    {
        return 'Users Exercise';
    }

    public static function getPluralLabel(): string
    {
        return 'Users Exercise';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('Users Exercise')
            ->description('Show user generated for exercise (latihan soal) module')
            ->defaultSort('created_at', 'DESC')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Full Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email Address')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('generate_count')
                    ->label('Generated Exercise Count')
                    ->getStateUsing(fn (User $record) => $record->exerciseHistory()->count())
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('User Register')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                //
            ])
            ->actions([
                // 
            ])
            ->bulkActions([
                // 
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUserExercises::route('/'),
        ];
    }
}

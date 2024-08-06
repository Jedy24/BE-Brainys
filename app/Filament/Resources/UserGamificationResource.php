<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserGamificationResource\Pages;
use App\Filament\Resources\UserGamificationResource\RelationManagers;
use App\Models\User;
use App\Models\UserGamification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserGamificationResource extends Resource
{
    protected static ?string $model = User::class;
    
    protected static ?int $navigationSort = 7;

    protected static ?string $navigationGroup = 'Users Modules';

    protected static ?string $navigationLabel = 'Users Gamification';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getLabel(): string
    {
        return 'Users Gamification';
    }

    public static function getPluralLabel(): string
    {
        return 'Users Gamification';
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
            ->heading('Users Bahan Ajar')
            ->description('Show user generated for gamification (materi gamifikasi) module')
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
                    ->label('Generated Gamification Count')
                    ->getStateUsing(fn (User $record) => $record->gamificationHistory()->count())
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

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUserGamifications::route('/'),
        ];
    }
}

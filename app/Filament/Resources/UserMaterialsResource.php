<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserMaterialsResource\Pages;
use App\Filament\Resources\UserMaterialsResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserMaterialsResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationGroup = 'User Modules';

    protected static ?string $navigationLabel = 'User Materials';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getLabel(): string
    {
        return 'User Material';
    }

    public static function getPluralLabel(): string
    {
        return 'Users Material';
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
            ->heading('Users Material')
            ->description('Show user generated for material module')
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
                    ->label('Generate Material Count')
                    ->getStateUsing(fn (User $record) => $record->materialHistory()->count())
                    ->alignCenter()
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
            'index' => Pages\ListUserMaterials::route('/'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AutoInviteEmailResource\Pages;
use App\Filament\Resources\AutoInviteEmailResource\RelationManagers;
use App\Models\AutoInviteEmail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AutoInviteEmailResource extends Resource
{
    protected static ?string $model = AutoInviteEmail::class;

    protected static ?int $navigationSort = 12;

    protected static ?string $navigationGroup = 'System';

    protected static ?string $navigationLabel = 'Auto Invitation';

    protected static ?string $navigationIcon = 'heroicon-m-cursor-arrow-ripple';

    public static function getLabel(): string
    {
        return 'Auto Invitation';
    }

    public static function getPluralLabel(): string
    {
        return 'Auto Invitation';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('email_domain')
                    ->label('Email Domain')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Is Active')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('email_domain')
                    ->label('Email Domain')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Is Active')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
            'index' => Pages\ListAutoInviteEmails::route('/'),
            'create' => Pages\CreateAutoInviteEmail::route('/create'),
            'edit' => Pages\EditAutoInviteEmail::route('/{record}/edit'),
        ];
    }
}

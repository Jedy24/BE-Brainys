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

    protected static ?int $navigationSort = 18;

    protected static ?string $navigationGroup = 'Sistem';

    protected static ?string $navigationLabel = 'Undangan Email';

    protected static ?string $navigationIcon = 'heroicon-m-cursor-arrow-ripple';

    public static function getLabel(): string
    {
        return 'Undangan Email';
    }

    public static function getPluralLabel(): string
    {
        return 'Undangan Email';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('email_domain')
                    ->label('Domain Alamat Email')
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
        ->heading('Undangan Email')
        ->description('Kelola domain email yang diperbolehkan untuk otomasi undangan')
            ->columns([
                Tables\Columns\TextColumn::make('email_domain')
                    ->label('Domain Alamat Email')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
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

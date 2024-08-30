<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UpdateMessageResource\Pages;
use App\Filament\Resources\UpdateMessageResource\RelationManagers;
use App\Models\UpdateMessage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UpdateMessageResource extends Resource
{
    protected static ?string $model = UpdateMessage::class;
    
    protected static ?int $navigationSort = 19;

    protected static ?string $navigationGroup = 'Sistem';

    protected static ?string $navigationLabel = 'Pesan Pembaharuan';

    protected static ?string $navigationIcon = 'heroicon-c-megaphone';

    public static function getLabel(): string
    {
        return 'Pesan Pembaharuan';
    }

    public static function getPluralLabel(): string
    {
        return 'Pesan Pembaharuan';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('version')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\Textarea::make('message')
                    ->required()
                    ->maxLength(65535)
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
        ->heading('Pesan Pembaharuan')
        ->description('Kelola pembaharuan pesan kepada pengguna')
            ->columns([
                Tables\Columns\TextColumn::make('version')
                    ->searchable(),
                Tables\Columns\TextColumn::make('message')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
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
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListUpdateMessages::route('/'),
            'create' => Pages\CreateUpdateMessage::route('/create'),
            'edit' => Pages\EditUpdateMessage::route('/{record}/edit'),
        ];
    }
}

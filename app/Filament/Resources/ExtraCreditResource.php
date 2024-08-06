<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ExtraCreditResource\Pages;
use App\Filament\Resources\ExtraCreditResource\RelationManagers;
use App\Models\ExtraCredit;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ExtraCreditResource extends Resource
{
    protected static ?string $model = ExtraCredit::class;

    protected static ?int $navigationSort = 11;

    protected static ?string $navigationGroup = 'Package & Credit';

    protected static ?string $navigationIcon = 'heroicon-o-circle-stack';

    public static function getLabel(): string
    {
        return 'Extra Credit';
    }

    public static function getPluralLabel(): string
    {
        return 'Extra Credits';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Extra Credit Name')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('credit_amount')
                    ->label('Credit Amount')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('price')
                    ->label('Price (Rp)')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Extra Credit Name')
                    ->searchable(),
                Tables\Columns\TextColumn::make('credit_amount')
                    ->label('Credit Amount')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Price (Rp)')
                    ->formatStateUsing(fn (string $state): string => 'Rp ' . number_format($state, 0, ',', '.'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated At')
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
            'index' => Pages\ListExtraCredits::route('/'),
            'create' => Pages\CreateExtraCredit::route('/create'),
            'edit' => Pages\EditExtraCredit::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentMethodResource\Pages;
use App\Filament\Resources\PaymentMethodResource\RelationManagers;
use App\Models\PaymentMethod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentMethodResource extends Resource
{
    protected static ?string $model = PaymentMethod::class;

    protected static ?int $navigationSort = 17;

    protected static ?string $navigationGroup = 'Sistem';

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    public static function getLabel(): string
    {
        return 'Metode Pembayaran';
    }

    public static function getPluralLabel(): string
    {
        return 'Metode Pembayaran';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\FileUpload::make('thumbnail')
                    ->label('Logo')
                    ->image()
                    ->imageEditor()
                    ->imageEditorAspectRatios([
                        '1:1',
                    ])
                    ->columnSpanFull()
                    ->required(),
                Forms\Components\TextInput::make('name')
                    ->label('Metode Pembayaran')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('code')
                    ->label('Kode Metode')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('provider')
                    ->label('Provider Gateway')
                    ->options([
                        'PAYDISINI' => 'Paydisini',
                    ])
                    ->required(),
                Forms\Components\TextInput::make('provider_code')
                    ->label('Provider Gateway Code/ID API')
                    ->required()
                    ->maxLength(255),
                Forms\Components\Select::make('category')
                    ->label('Payment Category')
                    ->options([
                        'virtual_account' => 'Virtual Account',
                        'e_wallet' => 'E-Wallet',
                        'others' => 'Others',
                    ])
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\RichEditor::make('description')
                    ->label('Description')
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('status')
                    ->label('Status')
                    ->required(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('Metode Pembayaran')
            ->description('Kelola metode pembayaran sistem')
            ->columns([
                Tables\Columns\ImageColumn::make('thumbnail')
                    ->label('Logo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('Metode Pembayaran')
                    ->searchable(),
                Tables\Columns\TextColumn::make('code')
                    ->label('Kode Metode')
                    ->searchable(),
                Tables\Columns\IconColumn::make('status')
                    ->boolean(),
                Tables\Columns\TextColumn::make('provider')
                    ->label('Provider Gateway')
                    ->searchable(),
                Tables\Columns\TextColumn::make('provider_code')
                    ->label('Provider Gateway Code/ID API')
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
            'index' => Pages\ListPaymentMethods::route('/'),
            'create' => Pages\CreatePaymentMethod::route('/create'),
            'edit' => Pages\EditPaymentMethod::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ModuleCreditChargeResource\Pages;
use App\Filament\Resources\ModuleCreditChargeResource\RelationManagers;
use App\Models\ModuleCreditCharge;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ModuleCreditChargeResource extends Resource
{
    protected static ?string $model = ModuleCreditCharge::class;

    protected static ?int $navigationSort = 18;

    protected static ?string $navigationGroup = 'Sistem';

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    public static function getLabel(): string
    {
        return 'Modul Kredit';
    }

    public static function getPluralLabel(): string
    {
        return 'Modul Kredit';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Module')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('slug')
                    ->label('Slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\TextInput::make('credit_charged_generate')
                    ->label('Kredit Dikenakan (Generate)')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('credit_charged_docx')
                    ->label('Kredit Dikenakan (DOCX)')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('credit_charged_pptx')
                    ->label('Kredit Dikenakan (PPTX)')
                    ->numeric()
                    ->required(),
                Forms\Components\TextInput::make('credit_charged_xlsx')
                    ->label('Kredit Dikenakan (XLSX)')
                    ->numeric()
                    ->required(),
                Forms\Components\Toggle::make('status')
                    ->label('Status Aktif')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('Modul Kredits')
            ->description('Kelola biaya kredit untuk setiap modul')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Module')
                    ->searchable(),
                Tables\Columns\TextColumn::make('slug')
                    ->label('Slug')
                    ->searchable(),
                Tables\Columns\TextColumn::make('credit_charged_generate')
                    ->label('Kredit Dikenakan (Generate)')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('credit_charged_docx')
                    ->label('Kredit Dikenakan (DOCX)')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('credit_charged_pptx')
                    ->label('Kredit Dikenakan (PPTX)')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\TextColumn::make('credit_charged_xlsx')
                    ->label('Kredit Dikenakan (XLSX)')
                    ->numeric()
                    ->sortable(),
                Tables\Columns\IconColumn::make('status')
                    ->boolean()
                    ->label('Status Aktif'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Dibuat Pada')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui Pada')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                // Tambahkan filter jika diperlukan
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
            // Tambahkan relasi jika diperlukan
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListModuleCreditCharges::route('/'),
            'create' => Pages\CreateModuleCreditCharge::route('/create'),
            'edit' => Pages\EditModuleCreditCharge::route('/{record}/edit'),
        ];
    }
}

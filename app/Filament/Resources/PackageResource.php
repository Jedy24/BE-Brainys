<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PackageResource\Pages;
use App\Filament\Resources\PackageResource\RelationManagers;
use App\Models\Package;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PackageResource extends Resource
{
    protected static ?string $model = Package::class;

    protected static ?int $navigationSort = 15;

    protected static ?string $navigationGroup = 'Produk';

    protected static ?string $navigationIcon = 'heroicon-o-tag';

    public static function getLabel(): string
    {
        return 'Paket';
    }

    public static function getPluralLabel(): string
    {
        return 'Paket';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nama Paket')
                    ->required()
                    ->maxLength(225),
                Forms\Components\Select::make('type')
                    ->label('Tipe Paket')
                    ->options([
                        'free' => 'Free',
                        'monthly' => 'Bulanan',
                        'annually' => 'Tahunan',
                    ])
                    ->required(),
                Forms\Components\Textarea::make('description')
                    ->label('Deskripsi')
                    ->required()
                    ->maxLength(525)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('credit_add_monthly')
                    ->label('Kredit per Bulan')
                    ->required()
                    ->numeric(),
                Forms\Components\TextInput::make('price')
                    ->label('Harga')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),
                Forms\Components\Section::make()->schema([
                    Forms\Components\Repeater::make('details')
                        ->relationship()
                        ->label('Deskripsi Keuntungan')
                        ->schema([
                            Forms\Components\TextInput::make('name')
                                ->required()
                                ->maxLength(255),
                        ])
                        ->reorderable(true)
                        ->reorderableWithButtons()
                        ->cloneable()
                        ->orderColumn('id')
                        ->addActionLabel('Tambah Deskripsi Benefit Keuntungan')
                ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('Paket')
            ->description('Kelola produk paket untuk pengguna')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Paket')
                    ->description(fn(Package $record): string => $record->description)
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label('Tipe Paket')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'free' => 'gray',
                        'monthly' => 'primary',
                        'annually' => 'success',
                    })
                    ->formatStateUsing(fn(string $state) => ucfirst($state)),
                Tables\Columns\TextColumn::make('credit_add_monthly')
                    ->label('Kredit per Bulan')
                    ->sortable(),
                Tables\Columns\TextColumn::make('price')
                    ->label('Harga (Rp)')
                    ->sortable()
                    ->formatStateUsing(fn(string $state): string => 'Rp ' . number_format($state, 0, ',', '.'))
            ])
            ->filters([
                // Add filters here if needed (e.g., by package type)
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
            'index' => Pages\ListPackages::route('/'),
            'create' => Pages\CreatePackage::route('/create'),
            'edit' => Pages\EditPackage::route('/{record}/edit'),
        ];
    }
}

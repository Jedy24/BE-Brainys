<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserPackageResource\Pages;
use App\Filament\Resources\UserPackageResource\RelationManagers;
use App\Models\UserPackage;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserPackageResource extends Resource
{
    protected static ?string $model = UserPackage::class;

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationGroup = 'User';

    protected static ?string $navigationLabel = 'Users Package';

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    public static function getLabel(): string
    {
        return 'User Package';
    }

    public static function getPluralLabel(): string
    {
        return 'User Packages';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('id_user')
                    ->relationship('user', 'email')
                    ->label('User')
                    ->required(),
                Forms\Components\Select::make('id_package')
                    ->relationship('package', 'name')
                    ->label('Package')
                    ->required(),
                Forms\Components\DateTimePicker::make('enroll_at'),
                Forms\Components\DateTimePicker::make('expired_at'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.email')
                    ->label('User Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('package.name')
                    ->label('Package')
                    ->searchable(),
                Tables\Columns\TextColumn::make('enroll_at')
                    ->label('Enroll Date')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('expired_at')
                    ->label('Expiry Date')
                    ->dateTime(),
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
            'index' => Pages\ListUserPackages::route('/'),
            'create' => Pages\CreateUserPackage::route('/create'),
            'edit' => Pages\EditUserPackage::route('/{record}/edit'),
        ];
    }
}

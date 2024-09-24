<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserPackageResource\Pages;
use App\Filament\Resources\UserPackageResource\RelationManagers;
use App\Models\Package;
use App\Models\UserPackage;
use Carbon\Carbon;
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

    protected static ?int $navigationSort = 12;

    protected static ?string $navigationGroup = 'Pengguna';

    protected static ?string $navigationLabel = 'Paket Pengguna';

    protected static ?string $navigationIcon = 'heroicon-o-user-circle';

    public static function getLabel(): string
    {
        return 'Paket Pengguna';
    }

    public static function getPluralLabel(): string
    {
        return 'Paket Pengguna';
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
                    ->getOptionLabelFromRecordUsing(function ($record) {
                        return ucwords($record->name) . ' (' . ucwords($record->type) . ')';
                    })
                    ->required()
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, ?UserPackage $record) {
                        if ($record) {
                            $originalPackageId = $record->id_package;
                            $newPackageId = $state;

                            if ($originalPackageId !== $newPackageId) {
                                $newPackageType = Package::find($newPackageId)->type;
                                $expiryDate = now();

                                if ($newPackageType === 'monthly') {
                                    $expiryDate->addDays(30);
                                } elseif ($newPackageType === 'annually') {
                                    $expiryDate->addYear();
                                }

                                $set('expired_at', $expiryDate->toDateTimeString()); // Convert to string
                            }
                        }
                    }),

                Forms\Components\DateTimePicker::make('enroll_at')
                    ->label('Enroll Date')
                    ->reactive()
                    ->afterStateUpdated(function ($state, callable $set, ?UserPackage $record) {
                        if ($record) {
                            $packageType = $record->package->type;
                            $expiryDate = $state ? Carbon::parse($state) : now();

                            if ($packageType === 'monthly') {
                                $expiryDate->addDays(30);
                            } elseif ($packageType === 'annually') {
                                $expiryDate->addYear();
                            }

                            $set('expired_at', $expiryDate->toDateTimeString()); // Convert to string
                        }
                    }),

                Forms\Components\DateTimePicker::make('expired_at')
                    ->label('Expiry Date')
                    ->afterStateUpdated(function ($state, callable $set, ?UserPackage $record) {
                        if (!$record) {
                            $packageType = $set('id_package') ? Package::find($set('id_package'))->type : null;
                            $expiryDate = now();

                            if ($packageType === 'monthly') {
                                $expiryDate->addDays(30);
                            } elseif ($packageType === 'annually') {
                                $expiryDate->addYear();
                            }

                            $set('expired_at', $expiryDate->toDateTimeString()); // Convert to string
                        }
                    }),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('Paket Pengguna')
            ->description('Kelola paket akun pengguna Brainys')
            ->columns([
                Tables\Columns\TextColumn::make('user.email')
                    ->label('Alamat Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('package.name')
                    ->label('Paket Tertaut')
                    ->searchable()
                    ->formatStateUsing(function ($record) {
                        return ucwords($record->package->name) . ' (' . ucwords($record->package->type) . ')';
                    }),
                Tables\Columns\TextColumn::make('enroll_at')
                    ->label('Tertaut Pada')
                    ->dateTime(),
                Tables\Columns\TextColumn::make('expired_at')
                    ->label('Kadaluarsa Pada')
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

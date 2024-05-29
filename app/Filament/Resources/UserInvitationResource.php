<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserInvitationResource\Pages;
use App\Filament\Resources\UserInvitationResource\RelationManagers;
use App\Forms\Components\InviteCodeInput;
use App\Models\UserInvitation;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UserInvitationResource extends Resource
{
    protected static ?string $model = UserInvitation::class;

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationGroup = 'Master';

    protected static ?string $navigationLabel = 'Users Invitation';

    protected static ?string $navigationIcon = 'heroicon-o-user-plus';

    public static function getLabel(): string
    {
        return 'User Invitation';
    }

    public static function getPluralLabel(): string
    {
        return 'User Invitation';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('invite_code')
                    ->required()
                    ->maxLength(255)
                    ->default(fn () => self::generateRandomCode(8)),
                Forms\Components\DateTimePicker::make('expired_at')
                    ->default(Carbon::now()->addDays(30)),
                Forms\Components\Hidden::make('is_used')
                    ->default(false),
            ]);
    }

    /**
     * Generate a random alphanumeric string.
     *
     * @param int $length
     * @return string
     */
    protected static function generateRandomCode($length = 8): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $charactersLength = strlen($characters);
        $randomString = '';
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, $charactersLength - 1)];
        }
        return $randomString;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('invite_code')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_used')
                    ->boolean(),
                Tables\Columns\TextColumn::make('expired_at')
                    ->dateTime()
                    ->sortable(),
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
            'index' => Pages\ListUserInvitations::route('/'),
            'create' => Pages\CreateUserInvitation::route('/create'),
            'edit' => Pages\EditUserInvitation::route('/{record}/edit'),
        ];
    }
}

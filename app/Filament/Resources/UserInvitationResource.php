<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserInvitationResource\Pages;
use App\Filament\Resources\UserInvitationResource\RelationManagers;
use App\Forms\Components\InviteCodeInput;
use App\Http\Controllers\Api\SendInvitationController;
use App\Models\User;
use App\Models\UserInvitation;
use Carbon\Carbon;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Http;

class UserInvitationResource extends Resource
{
    protected static ?string $model = UserInvitation::class;

    protected static ?int $navigationSort = 12;

    protected static ?string $navigationGroup = 'User';

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
            ->heading('Users Invitation')
            ->description('Manage Brainys Invitation')
            ->defaultSort('created_at', 'DESC')
            ->columns([
                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable(),
                Tables\Columns\TextColumn::make('invite_code')
                    ->label('Invite Code')
                    ->searchable(),
                Tables\Columns\IconColumn::make('is_used')
                    ->label('Used')
                    ->alignCenter()
                    ->boolean(),
                Tables\Columns\TextColumn::make('expired_at')
                    ->label('Expiration Date')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Action::make('sendInvitation')
                    ->label('Send Invitation')
                    ->icon('heroicon-o-envelope')
                    ->action(function (UserInvitation $record) {
                        static::sendInvitation($record);
                    })
                    ->requiresConfirmation()
                    ->color('success'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
                Tables\Actions\BulkAction::make('sendBulkInvitations')
                    ->label('Send Invitations')
                    ->icon('heroicon-o-envelope')
                    ->action(function (array $records) {
                        foreach ($records as $record) {
                            static::sendInvitation($record);
                        }
                    })
                    ->requiresConfirmation()
                    ->color('success'),
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

    /**
     * Get the base URL for the API.
     *
     * @return string
     */
    protected static function getApiBaseUrl(): string
    {
        // You can get this from the configuration or environment variable
        return env('APP_URL', 'https://be.brainys.oasys.id');
    }

    /**
     * Send an invitation.
     */
    public static function sendInvitation(UserInvitation $user)
    {
        try {
            // Create a new request instance
            $request = new \Illuminate\Http\Request();
            $request->replace(['email' => $user->email]);

            // Create an instance of the SendInvitationController
            $controller = new SendInvitationController();

            // Call the sendInvitation method
            $response = $controller->sendInvitation($request);

            // Handle response from the controller
            $responseData = $response->getData();

            if ($response->status() === 200 && $responseData->status === 'success') {
                Notification::make()
                    ->title('Kode undangan berhasil dikirim ke ' . $user->email)
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Gagal mengirim kode undangan: '.$responseData->message)
                    ->danger()
                    ->send();
            }
        } catch (\Exception $e) {
            Notification::make()
                ->title('Error: ' . $e->getMessage())
                ->danger()
                ->send();
        }
    }
}

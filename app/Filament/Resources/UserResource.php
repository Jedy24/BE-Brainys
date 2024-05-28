<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use App\Filament\Exports\UserExporter;
use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Actions\ExportBulkAction;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function getLabel(): string
    {
        return 'User';
    }

    public static function getPluralLabel(): string
    {
        return 'Users';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->required()
                    ->label('Full Name')
                    ->placeholder('Full Name')
                    ->columnSpan(fn ($livewire) => $livewire instanceof Pages\CreateUser ? 2 : 1),
                Forms\Components\TextInput::make('email')
                    ->email()
                    ->required()
                    ->label('Email Address')
                    ->placeholder('Email Address'),
                Forms\Components\TextInput::make('password')
                    ->password()
                    ->required()
                    ->hidden(fn ($livewire) => $livewire instanceof Pages\EditUser)
                    ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                    ->placeholder('Password')
                    ->label('Password'),
                Forms\Components\TextInput::make('school_name')
                    ->label('School Name')
                    ->placeholder('School Name'),
                Forms\Components\TextInput::make('profession')
                    ->placeholder('Profession')
                    ->label('Profession'),
                Forms\Components\Hidden::make('otp_verified_at')
                    ->default(now()->toDateTimeString()),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Full Name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email Address')
                    ->sortable(),
                Tables\Columns\IconColumn::make('profile_completed')
                    ->boolean()
                    ->label('Profile Complete')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('school_name')
                    ->label('School Name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('profession')
                    ->label('Profession')
                    ->sortable(),
                Tables\Columns\TextColumn::make('generate_count')
                    ->label('Generate Count')
                    ->getStateUsing(fn (User $record) => $record->generateAllSum())
                    ->alignCenter()
                    ->sortable(),
            ])
            ->filters([
                // Tambahkan filter yang relevan di sini jika diperlukan
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Action::make('resetPassword')
                    ->label('Reset Password')
                    ->action(function (User $record) {
                        static::forgotPassword($record);
                    })
                    ->requiresConfirmation()
                    ->color('primary'),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
                ExportBulkAction::make()
                    ->exporter(UserExporter::class),
            ]);
    }

    public static function getNavigation(): array
    {
        return [
            'label' => 'Users',
            'icon' => 'heroicon-o-users',
            'sort' => 2,
            'url' => static::getUrl('index'),
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

    public static function forgotPassword(User $user)
    {
        try {
            // Consume API
            $apiUrl = 'https://be.brainys.oasys.id/api/forgot-password';

            // Memanggil API dengan data email user yang akan dikirim mail notificationnya
            $response = Http::post($apiUrl, [
                'email' => $user->email,
            ]);

            // Handle response dari API
            $responseData = $response->json();

            if ($response->successful() && $responseData['status'] === 'success') {
                Notification::make()
                    ->title('Reset password email berhasil dikirim ke ' . $user->email)
                    ->success()
                    ->send();
            } else {
                Notification::make()
                    ->title('Gagal mengirim email reset password.')
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

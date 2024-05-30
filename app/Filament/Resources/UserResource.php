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
use Filament\Tables\Filters\Filter;
use Filament\Tables\Actions\ExportBulkAction;
use Illuminate\Contracts\Pagination\Paginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationGroup = 'Master';

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected function paginateTableQuery(Builder $query): Paginator
    {
        return $query->fastPaginate(($this->getTableRecordsPerPage() === 'all') ? $query->count() : $this->getTableRecordsPerPage());
    }

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
                Forms\Components\TextInput::make('limit_generate')
                    ->label('Limit Generate')
                    ->placeholder('Limit Generate'),
                Forms\Components\Select::make('roles')
                    ->label('Roles')
                    ->options([
                        'admin' => 'Admin',
                        'member' => 'Member',
                    ])
                    ->default('member')
                    ->required(),
                Forms\Components\Toggle::make('is_active')
                    ->label('Active')
                    ->default(true)
                    ->required()
                    ->columnSpanFull(),
                Forms\Components\Hidden::make('otp_verified_at')
                    ->default(now()->toDateTimeString()),
                Forms\Components\Hidden::make('profile_completed')
                    ->default(true),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('Users')
            ->description('Manage Brainys users')
            ->defaultSort('created_at', 'DESC')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Full Name')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Email Address')
                    ->sortable(),
                Tables\Columns\TextColumn::make('school_name')
                    ->label('School Name')
                    ->sortable(),
                Tables\Columns\TextColumn::make('profession')
                    ->label('Profession')
                    ->sortable(),
                Tables\Columns\IconColumn::make('profile_completed')
                    ->boolean()
                    ->label('Profile Complete')
                    ->alignCenter(),
                Tables\Columns\IconColumn::make('is_active')
                    ->boolean()
                    ->label('Active')
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('limit_generate')
                    ->label('Limit Generate')
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('generate_count')
                    ->label('Generate All Count')
                    ->getStateUsing(fn (User $record) => $record->generateAllSum())
                    ->alignCenter()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Register At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                Filter::make('profile_completed')
                    ->label('Profile Completed')
                    ->query(fn (Builder $query): Builder => $query->where('profile_completed', true)),
                Filter::make('name')
                    ->label('Full Name')
                    ->form([
                        Forms\Components\TextInput::make('name')
                            ->label('Full Name'),
                    ])
                    ->query(function (Builder $query, array $data) {
                        $query->where(function ($query) use ($data) {
                            if (isset($data['name'])) {
                                $query->where('name', 'like', '%' . $data['name'] . '%');
                            }
                        });
                    }),

            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
                Action::make('resetPassword')
                    ->label('Reset Password')
                    ->icon('heroicon-o-lock-closed')
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

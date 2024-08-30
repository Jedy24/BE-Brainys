<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserExercisesResource\Pages;
use App\Filament\Resources\UserExercisesResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\DB;

class UserExercisesResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?int $navigationSort = 23;

    protected static ?string $navigationGroup = 'Riwayat Modul Pengguna';

    protected static ?string $navigationLabel = 'Pengguna Modul Soal';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getLabel(): string
    {
        return 'Pengguna Modul Soal';
    }

    public static function getPluralLabel(): string
    {
        return 'Pengguna Modul Soal';
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('Pengguna Modul Soal')
            ->description('Menampilkan pengguna yang membuat modul latihan soal')
            ->defaultSort('last_generated', 'DESC')
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nama Lengkap')
                    // ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('Alamat Email')
                    // ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('generate_count')
                    ->label('Modul Dihasilkan')
                    // ->sortable()
                    ->alignCenter(),
                Tables\Columns\TextColumn::make('last_generated')
                    ->label('Terakhir Membuat')
                    ->dateTime('d M Y H:i:s')
                    // ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Terdaftar Sejak')
                    ->dateTime('d M Y H:i:s')
                    // ->sortable()
                    ->toggleable(isToggledHiddenByDefault: false),
            ])
            ->filters([
                // Filter jika diperlukan
            ])
            ->actions([
                // Actions jika diperlukan
            ])
            ->bulkActions([
                // Bulk actions jika diperlukan
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        return User::query()
            ->leftJoin('exercise_v2_histories', 'users.id', '=', 'exercise_v2_histories.user_id')
            ->select(
                'users.id',
                'users.name',
                'users.email',
                DB::raw('COUNT(exercise_v2_histories.id) as generate_count'),
                DB::raw('MAX(exercise_v2_histories.created_at) as last_generated'),
                'users.created_at'
            )
            ->groupBy('users.id', 'users.name', 'users.email', 'users.created_at')
            // Menambahkan kondisi having untuk filter generate_count > 0
            ->having('generate_count', '>', 0)
            ->orderBy('last_generated', 'DESC'); // Mengatur urutan default berdasarkan last_generated secara descending
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUserExercises::route('/'),
        ];
    }
}

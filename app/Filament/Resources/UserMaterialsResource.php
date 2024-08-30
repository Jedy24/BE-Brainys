<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserMaterialsResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class UserMaterialsResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?int $navigationSort = 21;

    protected static ?string $navigationGroup = 'Riwayat Modul Pengguna';

    protected static ?string $navigationLabel = 'Pengguna Modul Ajar';

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getLabel(): string
    {
        return 'Pengguna Modul Ajar';
    }

    public static function getPluralLabel(): string
    {
        return 'Pengguna Modul Ajar';
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            // Skema form
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('Pengguna Modul Bahan Ajar')
            ->description('Menampilkan pengguna yang membuat modul bahan ajar')
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
            ->leftJoin('modul_ajar_histories', 'users.id', '=', 'modul_ajar_histories.user_id')
            ->select(
                'users.id',
                'users.name',
                'users.email',
                DB::raw('COUNT(modul_ajar_histories.id) as generate_count'),
                DB::raw('MAX(modul_ajar_histories.created_at) as last_generated'),
                'users.created_at'
            )
            ->groupBy('users.id', 'users.name', 'users.email', 'users.created_at')
            // Menambahkan kondisi having untuk filter generate_count > 0
            ->having('generate_count', '>', 0)
            ->orderBy('last_generated', 'DESC'); // Mengatur urutan default berdasarkan last_generated secara descending
    }

    public static function getRelations(): array
    {
        return [
            // Relasi jika diperlukan
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUserMaterials::route('/'),
        ];
    }
}

<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Filament\Resources\TransactionResource\RelationManagers;
use App\Models\ExtraCredit;
use App\Models\Package;
use App\Models\Transaction;
use Filament\Forms;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Infolists\Components\TextEntry;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class TransactionResource extends Resource
{
    protected static ?string $model = Transaction::class;

    protected static ?int $navigationSort = 14;

    protected static ?string $navigationGroup = 'Transaksi';

    protected static ?string $navigationLabel = 'Kelola Transaksi';

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    public static function getLabel(): string
    {
        return 'Kelola Transaksi';
    }

    public static function getPluralLabel(): string
    {
        return 'Kelola Transaksi';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('id_user')
                    ->label('Pengguna')
                    ->relationship('user', 'name')
                    ->required()
                    ->disabled(),

                Forms\Components\DatePicker::make('transaction_date')
                    ->label('Tanggal Transaksi')
                    ->required()
                    ->disabled(),

                Forms\Components\TextInput::make('transaction_code')
                    ->label('Kode Transaksi')
                    ->required()
                    ->maxLength(255)
                    ->disabled(),

                Forms\Components\TextInput::make('transaction_name')
                    ->label('Nama Transaksi')
                    ->required()
                    ->maxLength(255)
                    ->disabled(),

                Forms\Components\TextInput::make('amount_sub')
                    ->label('Jumlah Subtotal')
                    ->prefix('Rp')
                    ->required()
                    ->dehydrated(false)
                    ->dehydrateStateUsing(fn($state) => (int) str_replace('.', '', $state))
                    ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                    ->disabled(),

                Forms\Components\TextInput::make('amount_fee')
                    ->label('Jumlah Biaya')
                    ->prefix('Rp')
                    ->required()
                    ->dehydrated(false)
                    ->dehydrateStateUsing(fn($state) => (int) str_replace('.', '', $state))
                    ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                    ->disabled(),

                Forms\Components\TextInput::make('amount_total')
                    ->label('Jumlah Total')
                    ->prefix('Rp')
                    ->required()
                    ->dehydrated(false)
                    ->dehydrateStateUsing(fn($state) => (int) str_replace('.', '', $state))
                    ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                    ->disabled(),

                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options([
                        'canceled' => 'Dibatalkan',
                        'pending' => 'Menunggu Pembayaran',
                        'success' => 'Selesai',
                        // 'completed' => 'Selesai',
                    ])
                    ->required(),

                Forms\Components\Repeater::make('details')
                    ->label('Detail Transaksi')
                    ->relationship()
                    ->maxItems(1)
                    ->schema([
                        Forms\Components\Select::make('item_type')
                            ->label('Tipe Item')
                            ->options([
                                'CREDIT' => 'CREDIT',
                                'PACKAGE' => 'PACKAGE',
                            ])
                            ->reactive()
                            ->disabled(),

                        Forms\Components\Select::make('item_id')
                            ->label('ID Item')
                            ->options(function (callable $get) {
                                $itemType = $get('item_type');
                                if ($itemType === 'CREDIT') {
                                    return ExtraCredit::pluck('name', 'id')->toArray();
                                }
                                if ($itemType === 'PACKAGE') {
                                    return Package::pluck('name', 'id')->toArray();
                                }
                                return [];
                            })
                            ->reactive()
                            ->disabled(),

                        Forms\Components\TextInput::make('item_price')
                            ->label('Harga Item')
                            ->formatStateUsing(function ($state) {
                                return 'Rp ' . number_format($state, 0, ',', '.');
                            })
                            ->disabled(),

                        Forms\Components\TextInput::make('item_qty')
                            ->label('Kuantitas Item')
                            ->default(1)
                            ->disabled(),
                    ]),

                Forms\Components\Repeater::make('payment')
                    ->label('Pembayaran Transaksi')
                    ->relationship()
                    ->maxItems(1)
                    ->schema([
                        Forms\Components\TextInput::make('pay_id')
                            ->label('ID Pembayaran')
                            ->maxLength(255)
                            ->disabled(),

                        Forms\Components\TextInput::make('unique_code')
                            ->label('Kode Unik')
                            ->maxLength(255)
                            ->disabled(),

                        Forms\Components\TextInput::make('service')
                            ->label('Layanan')
                            ->maxLength(255)
                            ->disabled(),

                        Forms\Components\TextInput::make('service_name')
                            ->label('Nama Layanan')
                            ->maxLength(255)
                            ->disabled(),

                        Forms\Components\TextInput::make('amount')
                            ->label('Jumlah Pembayaran')
                            ->prefix('Rp')
                            ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                            ->disabled(),

                        Forms\Components\TextInput::make('balance')
                            ->label('Saldo')
                            ->prefix('Rp')
                            ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                            ->disabled(),

                        Forms\Components\TextInput::make('fee')
                            ->label('Biaya')
                            ->prefix('Rp')
                            ->formatStateUsing(fn($state) => number_format($state, 0, ',', '.'))
                            ->disabled(),

                        Forms\Components\TextInput::make('type_fee')
                            ->label('Tipe Biaya')
                            ->maxLength(255)
                            ->disabled(),

                        Forms\Components\Select::make('status')
                            ->label('Status Pembayaran')
                            ->options([
                                'Pending' => 'Tertunda',
                                'Completed' => 'Sukses',
                                'Canceled' => 'Gagal',
                            ])
                            ->required(),

                        Forms\Components\DatePicker::make('expired')
                            ->label('Tanggal Kadaluarsa')
                            ->disabled(),

                        Forms\Components\TextInput::make('qrcode_url')
                            ->label('URL QRCode')
                            ->maxLength(255)
                            ->disabled(),

                        Forms\Components\TextInput::make('virtual_account')
                            ->label('Virtual Account')
                            ->maxLength(255)
                            ->disabled(),

                        Forms\Components\TextInput::make('checkout_url')
                            ->label('URL Checkout')
                            ->maxLength(255)
                            ->disabled(),

                        Forms\Components\TextInput::make('checkout_url_v2')
                            ->label('URL Checkout V2')
                            ->maxLength(255)
                            ->disabled(),

                        Forms\Components\TextInput::make('checkout_url_v3')
                            ->label('URL Checkout V3')
                            ->maxLength(255)
                            ->disabled(),

                        Forms\Components\TextInput::make('checkout_url_beta')
                            ->label('URL Checkout Beta')
                            ->maxLength(255)
                            ->disabled(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->heading('Kelola Transkasi')
            ->description('Kelola pembelian pengguna terhadap transaksi paket atau kredit Brainys')
            ->columns([

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu Transaksi')
                    ->dateTime('d F Y')
                    ->sortable()
                    ->description(fn($record): string => 'Jam: ' . $record->created_at->format('H:i:s')),

                Tables\Columns\TextColumn::make('transaction_code')
                    ->label('Kode Transaksi')
                    ->description(function (Transaction $record): string {
                        $detail = $record->details->first();

                        if ($detail->item_type === 'PACKAGE') {
                            $package = Package::find($detail->item_id);
                            $packageType = $package->type === 'annually' ? 'Tahunan' : ($package->type === 'monthly' ? 'Bulanan' : '');
                            return 'Pembelian ' . $record->transaction_name . ' (' . $packageType . ')';
                        }

                        return 'Pembelian ' . $record->transaction_name;
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('user.email')
                    ->label('Nama Pengguna')
                    ->searchable(),

                Tables\Columns\TextColumn::make('amount_sub')
                    ->label('Jumlah Subtotal')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('amount_fee')
                    ->label('Jumlah Biaya')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('amount_total')
                    ->label('Jumlah Total')
                    ->getStateUsing(fn($record) => 'Rp ' . number_format($record->amount_total, 0, ',', '.'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'pending' => 'warning',
                        'canceled' => 'danger',
                        'success' => 'success',
                        // 'completed' => 'success',
                    })
                    ->formatStateUsing(fn(string $state): string => match ($state) {
                        'pending' => 'Menunggu',
                        'canceled' => 'Dibatalkan',
                        'success' => 'Selesai',
                        // 'completed' => 'Selesai',
                    })
                    ->searchable(),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui Pada')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                // Tambahkan filter jika diperlukan
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label('Edit'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->label('Hapus Terpilih'),
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
            'index' => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'edit' => Pages\EditTransaction::route('/{record}/edit'),
        ];
    }
}

<?php

namespace App\Filament\Exports;

use App\Models\User;
use Filament\Actions\Exports\ExportColumn;
use Filament\Actions\Exports\Exporter;
use Filament\Actions\Exports\Models\Export;

class UserExporter extends Exporter
{
    protected static ?string $model = User::class;

    public static function getColumns(): array
    {
        return [
            ExportColumn::make('id')
                ->label('ID'),
            ExportColumn::make('name'),
            ExportColumn::make('email'),
            ExportColumn::make('school_name'),
            ExportColumn::make('profession'),
            ExportColumn::make('limit_generate'),
            ExportColumn::make('limit_generate_material'),
            ExportColumn::make('limit_generate_syllabus'),
            ExportColumn::make('limit_generate_exercise'),
            ExportColumn::make('email_verified_at'),
            ExportColumn::make('otp'),
            ExportColumn::make('otp_expiry'),
            ExportColumn::make('otp_verified_at'),
            ExportColumn::make('reset_token_expired'),
            ExportColumn::make('profile_completed'),
            ExportColumn::make('created_at'),
            ExportColumn::make('updated_at'),
        ];
    }

    public static function getCompletedNotificationBody(Export $export): string
    {
        $body = 'Your user export has completed and ' . number_format($export->successful_rows) . ' ' . str('row')->plural($export->successful_rows) . ' exported.';

        if ($failedRowsCount = $export->getFailedRowsCount()) {
            $body .= ' ' . number_format($failedRowsCount) . ' ' . str('row')->plural($failedRowsCount) . ' failed to export.';
        }

        return $body;
    }
}

<?php

namespace App\Exports;

use App\Models\Package;
use App\Models\UserPackage;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ReportUserExpiredExport implements FromCollection, WithHeadings
{
    protected $now;

    public function __construct($now)
    {
        $this->now = $now;
    }

    public function collection()
    {
        $now = Carbon::now();
        $freePackageIds = Package::where('type', 'free')->pluck('id');

        return UserPackage::with('package', 'user')
            ->where('expired_at', '<=', $now)
            ->whereNotIn('id_package', $freePackageIds)
            ->get()
            ->map(function ($userPackage) {
                $expiredAt = $userPackage->expired_at->format('d M Y');
                $daysRemaining = $userPackage->expired_at->diffInDays(now(), false);

                return [
                    $userPackage->user->email,
                    $userPackage->user->name,
                    $userPackage->package->name,
                    $expiredAt,
                    'Expired - Telah Diubah',
                ];
            });
    }

    public function headings(): array
    {
        return [
            'Email',
            'Name',
            'Package',
            'Expired At',
            'Status',
        ];
    }
}

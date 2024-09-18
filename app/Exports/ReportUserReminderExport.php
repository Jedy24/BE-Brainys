<?php

namespace App\Exports;

use App\Models\Package;
use App\Models\UserPackage;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;

class ReportUserReminderExport implements FromCollection
{
    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $now = Carbon::now();
        $threeDaysFromNow = Carbon::now()->addDays(3);
        $freePackageIds = Package::where('type', 'free')->pluck('id');

        return UserPackage::with('package', 'user')
            ->whereBetween('expired_at', [$now, $threeDaysFromNow])
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
                    'Masa aktif akan habis dalam '.$daysRemaining.' hari',
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

<?php

namespace App\Exports;

use App\Models\Package;
use App\Models\User;
use App\Models\UserPackage;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;

class ReportUserAddedCreditExport implements FromCollection
{
    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        $now = Carbon::now();
        $dayNow = $now->day;

        $packageIds = Package::where('type', 'annually')->pluck('id');

        return UserPackage::with('package', 'user')
            ->whereIn('id_package', $packageIds)
            ->whereDay('enroll_at', $dayNow)
            ->get()
            ->map(function ($userPackage) {
                $expiredAt = $userPackage->expired_at->format('d M Y');
                $daysRemaining = $userPackage->expired_at->diffInDays(now(), false);

                $credit_amount = Package::where('id', $userPackage->id_package)->pluck('credit_add_monthly')->first();

                return [
                    $userPackage->user->email,
                    $userPackage->user->name,
                    $userPackage->package->name,
                    $expiredAt,
                    $credit_amount.' Kredit ditambahkan',
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

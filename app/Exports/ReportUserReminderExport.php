<?php

namespace App\Exports;

use App\Models\Package;
use App\Models\UserPackage;
use Carbon\Carbon;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class ReportUserReminderExport implements FromCollection, WithHeadings
{
    protected $days;

    // Constructor to accept number of days before expiration
    public function __construct($days)
    {
        $this->days = $days;
    }

    /**
    * @return \Illuminate\Support\Collection
    */
    public function collection()
    {
        $now = Carbon::now();
        $targetDate = Carbon::now()->addDays($this->days); // Adding days dynamically
        $freePackageIds = Package::where('type', 'free')->pluck('id');

        return UserPackage::with('package', 'user')
            ->whereBetween('expired_at', [$now, $targetDate])
            ->whereNotIn('id_package', $freePackageIds)
            ->get()
            ->map(function ($userPackage) use ($now) {
                $expiredAt = $userPackage->expired_at->format('d M Y');
                $daysRemaining = $userPackage->expired_at->diffInDays($now, false);

                return [
                    $userPackage->user->email,
                    $userPackage->user->name,
                    $userPackage->package->name,
                    $expiredAt,
                    'Masa aktif akan habis dalam '.$daysRemaining.' hari',
                ];
            });
    }

    /**
     * Define the headings for the exported Excel sheet
     */
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
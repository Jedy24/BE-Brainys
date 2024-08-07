<?php

namespace App\Observers;

use App\Models\Package;
use App\Models\User;
use App\Models\UserPackage;
use Carbon\Carbon;

class UserObserver
{
    /**
     * Handle the User "created" event.
     */
    public function created(User $user): void
    {
        // Ambil paket dengan urutan pertama ASC
        $package = Package::orderBy('created_at', 'asc')->first();

        if ($package) {
            // Tentukan tanggal kedaluwarsa berdasarkan tipe paket
            $expiredAt = Carbon::now();

            if ($package->type === 'monthly') {
                $expiredAt = $expiredAt->addMonth();
            } elseif ($package->type === 'annually') {
                $expiredAt = $expiredAt->addYear();
            }

            // Buat entri di UserPackage
            UserPackage::create([
                'id_user' => $user->id,
                'id_package' => $package->id,
                'enroll_at' => Carbon::now(),
                'expired_at' => $expiredAt,
            ]);
        }
    }

    /**
     * Handle the User "updated" event.
     */
    public function updated(User $user): void
    {
        //
    }

    /**
     * Handle the User "deleted" event.
     */
    public function deleted(User $user): void
    {
        //
    }

    /**
     * Handle the User "restored" event.
     */
    public function restored(User $user): void
    {
        //
    }

    /**
     * Handle the User "force deleted" event.
     */
    public function forceDeleted(User $user): void
    {
        //
    }
}

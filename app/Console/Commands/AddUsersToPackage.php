<?php

namespace App\Console\Commands;

use App\Models\Package;
use App\Models\User;
use App\Models\UserPackage;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AddUsersToPackage extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'add:users-to-package';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $packageId = $this->argument('packageId');

        // Validate if packageId exists in the database
        $package = Package::find($packageId);
        if (!$package) {
            $this->error('Package with the given ID does not exist.');
            return 1;
        }

        $users = User::all();

        foreach ($users as $user) {
            // Determine the expiration date based on the package type
            $expiredAt = Carbon::now();

            if ($package->type === 'monthly') {
                $expiredAt = $expiredAt->addMonth();
            } elseif ($package->type === 'annually') {
                $expiredAt = $expiredAt->addYear();
            }

            // Check if the user is already in the package
            $userPackage = UserPackage::where('id_user', $user->id)
                ->first();

            if ($userPackage) {
                $userPackage->update([
                    'id_package' => $packageId,
                    'enroll_at' => Carbon::now(),
                    'expired_at' => $expiredAt,
                ]);
            } else {
                UserPackage::create([
                    'id_user' => $user->id,
                    'id_package' => $packageId,
                    'enroll_at' => Carbon::now(),
                    'expired_at' => $expiredAt,
                ]);
            }
        }

        $this->info('All users have been updated or added to the package.');

        return 0;
    }
}

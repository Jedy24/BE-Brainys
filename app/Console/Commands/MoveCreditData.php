<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User; // Pastikan model User diimpor

class MoveCreditData extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:move-credit-data';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Move credit data for all users';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $users = User::limit(1000)->get();
        $data = [];

        foreach ($users as $user) {
            $credit = $user->limit_generate - $user->generateAllSum();

            if (User::where('id', $user->id)->update(['credit' => $credit])) {
                $this->info("Updated credit for {$user->name}: {$credit}");
            } else {
                $this->error("Failed to update credit for {$user->name}");
            }

            $data[] = [
                'Name' => $user->name,
                'Limit' => $user->limit_generate,
                'Used' => $user->generateAllSum(),
                'Credit' => $credit,
            ];
        }

        $this->table(
            ['Name', 'Limit', 'Used', 'Credit'],
            $data
        );

        $this->info('Credit data migration and update completed.');
    }
}
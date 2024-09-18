<?php

namespace App\Console\Commands;

use App\Exports\ReportUserReminderExport;
use App\Models\Package;
use App\Models\UserPackage;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use Telegram\Bot\Api;
use Telegram\Bot\FileUpload\InputFile;

class CheckPackageReminder extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:check-package-reminder';

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
        $now = Carbon::now();
        $threeDaysFromNow = Carbon::now()->addDays(3);

        $freePackageIds = Package::where('type', 'free')->pluck('id');

        $userPackages = UserPackage::with('package', 'user')
            ->whereBetween('expired_at', [$now, $threeDaysFromNow])
            ->whereNotIn('id_package', $freePackageIds)
            ->get();

        $reminderCount = 0;

        $formattedDate = Carbon::now()->format('d-m-Y');
        $fileName = "reminder_user_packages_{$formattedDate}.xlsx";
        $filePath = storage_path("app/public/report/{$fileName}");
        
        // Generate XLSX report
        Excel::store(new ReportUserReminderExport($now), "public/report/{$fileName}");

        $userPackages->map(function ($userPackage) use ($now, &$reminderCount) {
            $reminderCount++;
            
            $daysRemaining = Carbon::parse($userPackage->expired_at)->diffInDays($now, false);
            $userPackage->days_remaining = $daysRemaining;

            return $userPackage;
        });

        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
        $chatId = env('TELEGRAM_CHAT_ID');

        $message = "*✨ Laporan Pengguna Masa Aktif Paket Akan Berakhir - " . Carbon::now()->format('d M Y') . "✨*\n\n";
        $message .= "Jumlah Pengguna Paket Akan Berakhir: *$reminderCount Pengguna*\n\n";
        $message .= "Semua pengguna dengan masa aktif paket akan berakhir sudah berikan notifikasi peringatan, berikut dengan data terlampir.\n\n";
        $message .= "Terima Kasih!😎\n";

        $telegram->sendDocument([
            'chat_id' => $chatId,
            'document' => InputFile::create($filePath, $fileName),
            'caption' => $message,
            'parse_mode' => 'Markdown',
        ]);

        $this->info('Package reminder check completed, XLSX report generated, and report sent to Telegram.');
    }
}

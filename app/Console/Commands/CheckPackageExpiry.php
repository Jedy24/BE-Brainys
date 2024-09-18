<?php

namespace App\Console\Commands;

use App\Exports\ReportUserExpiredExport;
use Illuminate\Console\Command;
use Carbon\Carbon;
use App\Models\UserPackage;
use App\Models\Package;
use Maatwebsite\Excel\Facades\Excel;
use Telegram\Bot\Api;
use Telegram\Bot\FileUpload\InputFile;

class CheckPackageExpiry extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:check-package-expiry';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check package expiry and take action if necessary';

    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $now = Carbon::now();

        $freePackageIds = Package::where('type', 'free')->pluck('id');
        $userPackages = UserPackage::with('package', 'user')
            ->where('expired_at', '<=', $now)
            ->whereNotIn('id_package', $freePackageIds)
            ->get();

        $expiredCount = 0;

        $formattedDate = Carbon::now()->format('d-m-Y');
        $fileName = "expired_user_packages_{$formattedDate}.xlsx";
        $filePath = storage_path("app/public/report/{$fileName}");

        Excel::store(new ReportUserExpiredExport($now), "public/report/{$fileName}");

        $userPackages->map(function ($userPackage) use ($now, &$expiredCount) {
            $expiredCount++;
            $freePackage = Package::where('type', 'free')->first();

            if ($freePackage) {
                $userPackage->update([
                    'id_package' => $freePackage->id,
                    'enroll_at' => $now,
                    'expired_at' => $now,
                ]);
            }
        });

        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
        $chatId = env('TELEGRAM_CHAT_ID');

        $message = "*✨ Laporan Pengguna Masa Aktif Paket Berakhir - " . Carbon::now()->format('d M Y') . "✨*\n\n";
        $message .= "Jumlah Pengguna Kadaluarsa: *$expiredCount Pengguna*\n\n";
        $message .= "Semua pengguna dengan masa aktif paket berlangganan sudah diubah ke paket FREE, berikut dengan data terlampir.\n\n";
        $message .= "Terima Kasih!😎\n";

        $telegram->sendDocument([
            'chat_id' => $chatId,
            'document' => InputFile::create($filePath, $fileName),
            'caption' => $message,
            'parse_mode' => 'Markdown',
        ]);

        $this->info('Package expiry check completed, XLSX report generated, and report sent to Telegram.');
    }
}
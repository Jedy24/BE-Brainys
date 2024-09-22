<?php

namespace App\Console\Commands;

use App\Exports\ReportUserAddedCreditExport;
use App\Models\Package;
use App\Models\User;
use App\Models\UserPackage;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Maatwebsite\Excel\Facades\Excel;
use Telegram\Bot\Api;
use Telegram\Bot\FileUpload\InputFile;

class CheckMonthlyCredit extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'task:check-monthly-credit';

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
        $dayNow = $now->day;

        $packageIds = Package::where('type', 'annually')->pluck('id');

        // Ambil data dengan enroll_at yang memiliki hari yang sama, terlepas dari bulannya
        $userPackages = UserPackage::with('package', 'user')
            ->whereIn('id_package', $packageIds)
            ->whereDay('enroll_at', $dayNow)
            ->get();

        $addedCreditCount = 0;

        $formattedDate = Carbon::now()->format('d-m-Y');
        $fileName = "added_user_credit_{$formattedDate}.xlsx";
        $filePath = storage_path("app/public/report/{$fileName}");

        // Generate XLSX report
        Excel::store(new ReportUserAddedCreditExport($now), "public/report/{$fileName}");

        $userPackages->map(function ($userPackage) use ($now, &$addedCreditCount) {
            $addedCreditCount++;
            
            $credit_amount = Package::where('id', $userPackage->id_package)->pluck('credit_add_monthly')->first();
            User::where('id', $userPackage->id_user)->increment('credit', (int) $credit_amount);

            return $userPackage;
        });

        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
        $chatId = env('TELEGRAM_CHAT_ID');

        $message = "*✨ Laporan Pengguna Ditambahkan Kredit Bulanan - " . Carbon::now()->format('d M Y') . "✨*\n\n";
        $message .= "Jumlah Pengguna Ditambahkan Kredit: *$addedCreditCount Pengguna*\n\n";
        $message .= "Semua pengguna dengan _enroll_ tanggal " . Carbon::now()->format('d') . " sudah ditambahkan kredit bulanannya sesuai paket, berikut dengan data terlampir.\n\n";
        $message .= "Terima Kasih!😎\n";

        $telegram->sendDocument([
            'chat_id' => $chatId,
            'document' => InputFile::create($filePath, $fileName),
            'caption' => $message,
            'parse_mode' => 'Markdown',
        ]);

        $this->info('Check credit completed, XLSX report generated, and report sent to Telegram.');
    }
}

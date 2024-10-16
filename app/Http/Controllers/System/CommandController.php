<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Exports\ReportUserAddedCreditExport;
use App\Exports\ReportUserExpiredExport;
use App\Exports\ReportUserReminderExport;
use App\Mail\FreePlanNotification;
use App\Mail\PackageReminderNotification;
use App\Models\Package;
use App\Models\User;
use App\Models\UserPackage;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;
use Telegram\Bot\Api;
use Telegram\Bot\FileUpload\InputFile;

class CommandController extends Controller
{
    /**
     * Generate a monthly credit report, increment user credits based on the package,
     * and send the report to a Telegram channel.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkMonthlyCredit()
    {
        $now = Carbon::now();
        $dayNow = $now->day;

        // Fetch annually based package IDs
        $packageIds = Package::where('type', 'annually')->pluck('id');

        // Fetch user packages with enroll_at day matching today
        $userPackages = UserPackage::with('package', 'user')
            ->whereIn('id_package', $packageIds)
            ->whereDay('enroll_at', $dayNow)
            ->get();

        $addedCreditCount = 0;

        $formattedDate = $now->format('d-m-Y');
        $fileName = "added_user_credit_{$formattedDate}.xlsx";
        $filePath = "public/report/{$fileName}";

        // Generate the Excel report
        Excel::store(new ReportUserAddedCreditExport($now), $filePath);

        // Increment user credits
        $userPackages->map(function ($userPackage) use (&$addedCreditCount) {
            $addedCreditCount++;
            $credit_amount = Package::where('id', $userPackage->id_package)->pluck('credit_add_monthly')->first();
            User::where('id', $userPackage->id_user)->increment('credit', (int) $credit_amount);
        });

        // Send the report to Telegram
        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
        $chatId = env('TELEGRAM_CHAT_ID');

        $message = "*✨ Laporan Pengguna Ditambahkan Kredit Bulanan - " . Carbon::now()->format('d M Y') . "✨*\n\n";
        $message .= "Jumlah Pengguna Ditambahkan Kredit: *$addedCreditCount Pengguna*\n\n";
        $message .= "Semua pengguna dengan _enroll_ tanggal " . Carbon::now()->format('d') . " sudah ditambahkan kredit bulanannya.\n\n";
        $message .= "Terima Kasih!😎\n";

        $telegram->sendDocument([
            'chat_id' => $chatId,
            'document' => InputFile::create(Storage::path($filePath), $fileName),
            'caption' => $message,
            'parse_mode' => 'Markdown',
        ]);

        return response()->json(['status' => 'success', 'message' => 'Check monthly credit completed and report sent to Telegram.']);
    }

    /**
     * Check for expired packages, change them to the free package,
     * and send a report of expired packages to a Telegram channel.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkPackageExpiry()
    {
        $now = Carbon::now();

        // Fetch IDs of free packages
        $freePackageIds = Package::where('type', 'free')->pluck('id');

        // Fetch expired user packages, excluding free packages
        $userPackages = UserPackage::with('package', 'user')
            ->where('expired_at', '<=', $now)
            ->whereNotIn('id_package', $freePackageIds)
            ->get();

        $expiredCount = 0;

        $formattedDate = $now->format('d-m-Y');
        $fileName = "expired_user_packages_{$formattedDate}.xlsx";
        $filePath = "public/report/{$fileName}";

        // Generate the Excel report
        Excel::store(new ReportUserExpiredExport($now), $filePath);

        // Update user packages to free package
        $userPackages->map(function ($userPackage) use ($now, &$expiredCount) {
            $expiredCount++;
            $freePackage = Package::where('type', 'free')->first();

            if ($freePackage) {
                $userPackage->update([
                    'id_package' => $freePackage->id,
                    'enroll_at' => $now,
                    'expired_at' => $now,
                ]);

                $user = User::find($userPackage->id_user);

                Mail::to($user->email)->send(new FreePlanNotification($user, $userPackage));
            }
        });

        // Send the report to Telegram
        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
        $chatId = env('TELEGRAM_CHAT_ID');

        $message = "*✨ Laporan Pengguna Masa Aktif Paket Berakhir - " . Carbon::now()->format('d M Y') . "✨*\n\n";
        $message .= "Jumlah Pengguna Kadaluarsa: *$expiredCount Pengguna*\n\n";
        $message .= "Semua pengguna dengan masa aktif paket berlangganan sudah diubah ke paket FREE.\n\n";
        $message .= "Terima Kasih!😎\n";

        $telegram->sendDocument([
            'chat_id' => $chatId,
            'document' => InputFile::create(Storage::path($filePath), $fileName),
            'caption' => $message,
            'parse_mode' => 'Markdown',
        ]);

        return response()->json(['status' => 'success', 'message' => 'Package expiry check completed and report sent to Telegram.']);
    }

    /**
     * Send a reminder to users whose package is set to expire within three days.
     * Generates a report and sends it to a Telegram channel.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function checkPackageReminder()
    {
        $now = Carbon::now();
        $sevenDaysFromNow = $now->copy()->addDays(7)->startOfDay();
        $oneDayFromNow = $now->copy()->addDay()->startOfDay();

        // Fetch IDs of free packages
        $freePackageIds = Package::where('type', 'free')->pluck('id');

        // Fetch user packages that will expire in exactly 7 days
        $userPackagesSevenDays = UserPackage::with('package', 'user')
            ->whereDate('expired_at', '=', $sevenDaysFromNow)
            ->whereNotIn('id_package', $freePackageIds)
            ->get();

        // Fetch user packages that will expire in exactly 1 day
        $userPackagesOneDay = UserPackage::with('package', 'user')
            ->whereDate('expired_at', '=', $oneDayFromNow)
            ->whereNotIn('id_package', $freePackageIds)
            ->get();

        $reminderSevenDaysCount = 0;
        $reminderOneDayCount = 0;

        $formattedDate = $now->format('d-m-Y');

        // Generate separate Excel reports for 7 days and 1 day reminders
        $fileNameSevenDays = "reminder_user_packages_7days_{$formattedDate}.xlsx";
        $filePathSevenDays = "public/report/{$fileNameSevenDays}";
        Excel::store(new ReportUserReminderExport(7), $filePathSevenDays);

        $fileNameOneDay = "reminder_user_packages_1day_{$formattedDate}.xlsx";
        $filePathOneDay = "public/report/{$fileNameOneDay}";
        Excel::store(new ReportUserReminderExport(1), $filePathOneDay);

        // Calculate days remaining for each user package (7 days)
        $userPackagesSevenDays->map(function ($userPackage) use ($now, &$reminderSevenDaysCount) {
            $reminderSevenDaysCount++;
            $daysRemaining = Carbon::parse($userPackage->expired_at)->diffInDays($now, false);
            $userPackage->days_remaining = $daysRemaining;

            // Kirim email atau notifikasi lain ke user untuk 7 hari sebelum expired
            Mail::to($userPackage->user->email)->send(new PackageReminderNotification($userPackage->user, $userPackage->package, $userPackage));
        });

        // Calculate days remaining for each user package (1 day)
        $userPackagesOneDay->map(function ($userPackage) use ($now, &$reminderOneDayCount) {
            $reminderOneDayCount++;
            $daysRemaining = Carbon::parse($userPackage->expired_at)->diffInDays($now, false);
            $userPackage->days_remaining = $daysRemaining;

            // Kirim email atau notifikasi lain ke user untuk 1 hari sebelum expired
            Mail::to($userPackage->user->email)->send(new PackageReminderNotification($userPackage->user, $userPackage->package, $userPackage));
        });

        // Send the report to Telegram for both 7 days and 1 day reminders
        $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
        $chatId = env('TELEGRAM_CHAT_ID');

        $message = "*✨ Laporan Pengguna Paket Akan Berakhir - " . Carbon::now()->format('d M Y') . "✨*\n\n";
        $message .= "Jumlah Pengguna Paket Akan Berakhir dalam 7 hari: *$reminderSevenDaysCount Pengguna*\n";
        $message .= "Jumlah Pengguna Paket Akan Berakhir dalam 1 hari: *$reminderOneDayCount Pengguna*\n\n";
        $message .= "Semua pengguna sudah diberikan notifikasi.\n\n";
        $message .= "Terima Kasih!😎\n";

        // Send 7 days report
        $telegram->sendDocument([
            'chat_id' => $chatId,
            'document' => InputFile::create(Storage::path($filePathSevenDays), $fileNameSevenDays),
            'caption' => $message,
            'parse_mode' => 'Markdown',
        ]);

        // Send 1 day report
        $telegram->sendDocument([
            'chat_id' => $chatId,
            'document' => InputFile::create(Storage::path($filePathOneDay), $fileNameOneDay),
            'caption' => $message,
            'parse_mode' => 'Markdown',
        ]);

        return response()->json(['status' => 'success', 'message' => 'Package reminder check for 7 and 1 day completed and report sent to Telegram.']);
    }
}

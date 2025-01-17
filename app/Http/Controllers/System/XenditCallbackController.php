<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Mail\PaymentCancelNotification;
use App\Mail\PaymentSuccessNotification;
use App\Mail\PremiumPlanNotification;
use App\Models\ExtraCredit;
use App\Models\Package;
use App\Models\Transaction;
use App\Models\TransactionPayment;
use App\Models\User;
use App\Models\UserPackage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Telegram\Bot\Api;

class XenditCallbackController extends Controller
{
    /**
     * Handle the incoming callback request from Paydisini.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request)
    {
        
        // API Key
        // $apiKey = env('PAYDISINI_KEY');

        // Retrive data
        $data = $request->all();

        $xendit_id              = $data['id']; // ID transaksi
        $external_id            = $data['external_id']; // ID eksternal
        $user_id                = $data['user_id']; // User ID
        $is_high                = $data['is_high']; // Apakah prioritas tinggi
        $payment_method         = $data['payment_method']; // Metode pembayaran
        $status                 = strtoupper($data['status']); // Status pembayaran dalam huruf kecil
        $merchant_name          = $data['merchant_name']; // Nama merchant
        $amount                 = $data['amount']; // Total jumlah
        $paid_amount            = $data['paid_amount']; // Jumlah yang dibayar
        $bank_code              = $data['bank_code']; // Kode bank
        $paid_at                = $data['paid_at']; // Waktu pembayaran
        $payer_email            = $data['payer_email']; // Email pembayar
        $description            = $data['description']; // Deskripsi pembayaran
        $created                = $data['created']; // Waktu dibuat
        $updated                = $data['updated']; // Waktu diperbarui
        $currency               = $data['currency']; // Mata uang
        $payment_channel        = $data['payment_channel']; // Channel pembayaran
        $payment_destination    = $data['payment_destination']; // Tujuan pembayaran

        $transaction = Transaction::with('details', 'payment')
            ->where('transaction_code', $external_id)
            ->orderBy('created_at', 'desc')
            ->first();
        $details = $transaction->details->first();
        $transaction->details = $transaction->details->first();
        $transaction->payment = $transaction->payment->first();

        // Process the payment status
        if ($status === 'PAID') {
            if ($details->item_type === 'PACKAGE') {
                $expiredAt = Carbon::now();
                $package = Package::find($details->item_id);

                $userPackage = UserPackage::where('id_user', $transaction->id_user)
                    ->with('package')
                    ->first();

                if ($userPackage) {
                    if ($userPackage->package->type !== 'free') {
                        $expiredAt = Carbon::parse($userPackage->expired_at);
                        if ($package->type === 'monthly') {
                            $expiredAt = $expiredAt->addMonth();
                        } elseif ($package->type === 'annually') {
                            $expiredAt = $expiredAt->addYear();
                        }
                    } else {
                        $expiredAt = Carbon::now();
                        if ($package->type === 'monthly') {
                            $expiredAt = $expiredAt->addMonth();
                        } elseif ($package->type === 'annually') {
                            $expiredAt = $expiredAt->addYear();
                        }
                    }

                    $userPackage->update([
                        'id_package' => $details->item_id,
                        'enroll_at' => Carbon::now(),
                        'expired_at' => $expiredAt,
                    ]);
                } else {
                    if ($package->type === 'monthly') {
                        $expiredAt = $expiredAt->addMonth();
                    } elseif ($package->type === 'annually') {
                        $expiredAt = $expiredAt->addYear();
                    }

                    UserPackage::create([
                        'id_user' => $transaction->id_user,
                        'id_package' => $details->item_id,
                        'enroll_at' => Carbon::now(),
                        'expired_at' => $expiredAt,
                    ]);
                }

                User::where('id', $transaction->id_user)->increment('credit', (int) $package->credit_add_monthly);
            } else if ($details->item_type === 'CREDIT') {
                $credit = ExtraCredit::find($details->item_id);
                $credit_amount = $credit->credit_amount;

                User::where('id', $transaction->id_user)->increment('credit', (int) $credit_amount);
            }

            $user = User::where('id', $transaction->id_user)->first();
            // Mail::to($user->email)->send(new PaymentSuccessNotification($user, $transaction));

            Transaction::where('transaction_code', $external_id)->update(['status' => 'completed']);
            TransactionPayment::where('unique_code', $external_id)->update(['status' => $status]);

            $telegram = new Api(env('TELEGRAM_BOT_TOKEN'));
            $chatId = env('TELEGRAM_CHAT_ID');

            if ($transaction->details->item_type === 'PACKAGE') {
                $package = Package::find($transaction->details->item_id);
                $packageType = $package->type === 'annually' ? 'Tahunan' : ($package->type === 'monthly' ? 'Bulanan' : '');
                $jenisTransaksi = 'Pembelian ' . $transaction->transaction_name . ' (' . $packageType . ')';

                // Mail::to($user->email)->send(new PremiumPlanNotification($user, $transaction));
            } else if ($transaction->details->item_type === 'CREDIT') {
                $jenisTransaksi = 'Pembelian ' . $transaction->transaction_name;

                // Mail::to($user->email)->send(new ExtraCreditNotification($user, $transaction));
            }

            $message = "*🎉 Transaksi Sukses Diterima - " . Carbon::now()->format('d M Y') . "🎉*\n\n";
            $message .= "Transaksi dengan kode: *$external_id* berhasil diproses.\n";
            $message .= "Pengguna: *{$user->email}*\n";
            $message .= "Kategori: *{$details->item_type}*\n";
            $message .= "Item: *{$jenisTransaksi}*\n";
            $message .= "Status: *$status*\n\n";
            $message .= "Terima kasih! 😊\n";

            $telegram->sendMessage([
                'chat_id' => $chatId,
                'text' => $message,
                'parse_mode' => 'Markdown',
            ]);

            Log::info("Payment with unique code {$external_id} was successful.");
        } elseif ($status === 'Canceled') {
            // Email Payment Sccess
            $user = User::where('id', $transaction->id_user)->first();
            // Mail::to($user->email)->send(new PaymentCancelNotification($user, $transaction));

            Transaction::where('transaction_code', $external_id)->update(['status' => 'canceled']);
            TransactionPayment::where('unique_code', $external_id)->update(['status' => $status]);
            Log::info("Payment with unique code {$external_id} was canceled.");
        } else {
            return response()->json(['success' => false, 'message' => 'Unknown status'], 400);
        }

        return response()->json(['success' => true]);
    }
}

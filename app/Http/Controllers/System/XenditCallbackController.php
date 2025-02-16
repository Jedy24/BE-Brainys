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
use App\Services\ReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Telegram\Bot\Api;

class XenditCallbackController extends Controller
{
    protected $reportService;

    /**
     * Class Constructor
     *
     * @param ReportService $reportService Instance of ReportService for handling reports
     */
    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    /**
     * Handle the incoming callback request from Xendit.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request)
    {
        $data = $request->all();
        $transaction = $this->getTransaction($data['external_id']);

        if (!$transaction) {
            return response()->json(['success' => false, 'message' => 'Transaction not found'], 404);
        }

        $status = strtoupper($data['status']);
        $user = User::find($transaction->id_user);

        if ($status === 'PAID') {
            $this->handlePaidStatus($transaction, $data, $user);
        } elseif ($status === 'EXPIRED') {
            $this->handleExpiredStatus($transaction, $data, $user);
        } else {
            return response()->json(['success' => false, 'message' => 'Unknown status'], 400);
        }

        return response()->json(['success' => true]);
    }

    /**
     * Retrieve transaction details.
     *
     * @param string $externalId
     * @return Transaction|null
     */
    private function getTransaction($externalId)
    {
        return Transaction::with('details', 'payment')
            ->where('transaction_code', $externalId)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Handle PAID status.
     *
     * @param Transaction $transaction
     * @param array $data
     * @param User $user
     */
    private function handlePaidStatus($transaction, $data, $user)
    {
        $details = $transaction->details->first();
        $itemType = $details->item_type;

        if ($itemType === 'PACKAGE') {
            $this->updateUserPackage($transaction, $details);
        } elseif ($itemType === 'CREDIT') {
            $this->updateUserCredit($details);
        }

        // Update transaction status with additional data from webhook
        $this->updateTransactionStatus($transaction, $data['external_id'], 'completed', $data['status'], $data);

        // Send success notification
        $this->sendSuccessNotification($transaction, $user, $details);

        Log::info("Payment with unique code {$data['external_id']} was successful.");
    }

    /**
     * Update user package based on transaction.
     *
     * @param Transaction $transaction
     * @param mixed $details
     */
    private function updateUserPackage($transaction, $details)
    {
        $package = Package::find($details->item_id);
        $userPackage = UserPackage::where('id_user', $transaction->id_user)->first();

        $expiredAt = $this->calculateExpiredAt($userPackage, $package);

        if ($userPackage) {
            $userPackage->update([
                'id_package' => $details->item_id,
                'is_renewable' => 1,
                'enroll_at' => Carbon::now(),
                'expired_at' => $expiredAt,
            ]);
        } else {
            UserPackage::create([
                'id_user' => $transaction->id_user,
                'id_package' => $details->item_id,
                'enroll_at' => Carbon::now(),
                'expired_at' => $expiredAt,
            ]);
        }

        User::where('id', $transaction->id_user)->increment('credit', (int) $package->credit_add_monthly);
    }

    /**
     * Calculate expired_at date for user package.
     *
     * @param UserPackage|null $userPackage
     * @param Package $package
     * @return Carbon
     */
    private function calculateExpiredAt($userPackage, $package)
    {
        $expiredAt = Carbon::now();

        if ($userPackage && $userPackage->package->type !== 'free') {
            if ($userPackage->package->id === $package->id) {
                $expiredAt = Carbon::parse($userPackage->expired_at);
            }
        }

        if ($package->type === 'monthly') {
            return $expiredAt->addMonth();
        } elseif ($package->type === 'annually') {
            return $expiredAt->addYear();
        }

        return $expiredAt;
    }

    /**
     * Update user credit based on transaction.
     *
     * @param mixed $details
     */
    private function updateUserCredit($details)
    {
        $credit = ExtraCredit::find($details->item_id);

        User::where('id', $details->item_id)->increment('credit', (int) $credit->credit_amount);
    }

    /**
     * Handle EXPIRED status.
     *
     * @param Transaction $transaction
     * @param User $user
     */
    private function handleExpiredStatus($transaction, $data, $user)
    {
        // $this->updateTransactionStatus($transaction, $transaction->transaction_code, 'expired', 'EXPIRED');
        $this->updateTransactionStatus($transaction, $data['external_id'], 'canceled', $data['status'], $data);
        Mail::to($user->email)->send(new PaymentCancelNotification($user, $transaction));
        Log::info("Payment with unique code {$transaction->transaction_code} has expired.");
    }

    /**
     * Update transaction and payment status.
     *
     * @param Transaction $transaction
     * @param string $externalId
     * @param string $transactionStatus
     * @param string $paymentStatus
     * @param array $data Data from webhook
     */
    private function updateTransactionStatus($transaction, $externalId, $transactionStatus, $paymentStatus, $data)
    {
        // Update Transaction status
        Transaction::where('transaction_code', $externalId)->update(['status' => $transactionStatus]);

        // Update TransactionPayment status and additional fields
        TransactionPayment::where('unique_code', $externalId)->update([
            'status' => $paymentStatus,
            'pay_id' => $data['id'] ?? null, // ID dari Xendit
            'service' => $data['payment_method'] ?? null, // Misal: RETAIL_OUTLET
            'service_name' => $data['payment_channel'] ?? null, // Misal: ALFAMART
            'amount' => $data['amount'] ?? 0, // Jumlah pembayaran
            // 'paid_amount' => $data['paid_amount'] ?? 0, // Jumlah yang sudah dibayar
            // 'payment_destination' => $data['payment_destination'] ?? null, // Nomor tujuan pembayaran
            // 'expired' => isset($data['expired_date']) ? Carbon::parse($data['expired_date']) : null, // Waktu kedaluwarsa
        ]);
    }

    /**
     * Send success notification.
     *
     * @param Transaction $transaction
     * @param User $user
     * @param mixed $details
     */
    private function sendSuccessNotification($transaction, $user, $details)
    {
        $jenisTransaksi = $this->getTransactionType($transaction, $details);

        $message = "*🎉 Transaksi Sukses Diterima - " . Carbon::now()->format('d M Y') . "🎉*\n\n";
        $message .= "Transaksi dengan kode: *{$transaction->transaction_code}* berhasil diproses.\n";
        $message .= "Pengguna: *{$user->email}*\n";
        $message .= "Kategori: *{$details->item_type}*\n";
        $message .= "Item: *{$jenisTransaksi}*\n";
        $message .= "Status: *PAID*\n\n";
        $message .= "Terima kasih! 😊\n";

        $this->reportService->sendToTelegram(null, null, $message);
        $this->reportService->sendToDiscordChannel(null, null, $message, '1338678783061917770');

        Mail::to($user->email)->send(new PaymentSuccessNotification($user, $transaction));
    }

    /**
     * Get transaction type for notification.
     *
     * @param Transaction $transaction
     * @param mixed $details
     * @return string
     */
    private function getTransactionType($transaction, $details)
    {
        if ($details->item_type === 'PACKAGE') {
            $package = Package::find($details->item_id);
            $packageType = $package->type === 'annually' ? 'Tahunan' : ($package->type === 'monthly' ? 'Bulanan' : '');
            return 'Pembelian ' . $transaction->transaction_name . ' (' . $packageType . ')';
        } elseif ($details->item_type === 'CREDIT') {
            return 'Pembelian ' . $transaction->transaction_name;
        }

        return '';
    }
}

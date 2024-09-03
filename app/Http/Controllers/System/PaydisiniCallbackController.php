<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\ExtraCredit;
use App\Models\Package;
use App\Models\Transaction;
use App\Models\TransactionPayment;
use App\Models\User;
use App\Models\UserPackage;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaydisiniCallbackController extends Controller
{
    /**
     * Handle the incoming callback request from Paydisini.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function handle(Request $request)
    {
        $allowedIp = '84.247.150.90';

        // method
        if ($request->isMethod('get')) {
            return response()->json(['success' => false, 'message' => 'GET method is not allowed'], 405);
        }

        // Validate the request source IP
        if ($request->ip() !== $allowedIp) {
            return response()->json(['success' => false, 'message' => 'Unauthorized IP address'], 403);
        }

        // Retrieve parameters from the request
        $key        = $request->input('key');
        $payId      = $request->input('pay_id');
        $uniqueCode = $request->input('unique_code');
        $status     = $request->input('status');
        $signature  = $request->input('signature');

        // API Key
        $apiKey = env('PAYDISINI_KEY');

        // Generate the expected signature
        $expectedSignature = md5($apiKey . $uniqueCode . 'CallbackStatus');

        // Validate the request signature
        if ($signature !== $expectedSignature) {
            return response()->json(['success' => false, 'message' => 'Invalid signature', 'apikey' => $apiKey, 'siganture' => $expectedSignature, 'signature_make' => $signature], 400);
        }

        $transaction = Transaction::with('details')
            ->where('transaction_code', $uniqueCode)
            ->orderBy('created_at', 'desc')
            ->first();
        $details = $transaction->details->first();

        // Process the payment status
        if ($status === 'Success') {
            if ($details->item_type === 'PACKAGE') {
                $expiredAt = Carbon::now();
                $package = Package::find($details->item_id);

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

                User::where('id', $transaction->id_user)->increment('limit_generate', $details->credit_add_monthly);
            } else if ($details->item_type === 'CREDIT') {
                $credit = ExtraCredit::find($details->item_id);
                $credit_amount = $credit->credit_amount;

                User::where('id', $transaction->id_user)->increment('limit_generate', $credit_amount);
            }

            Transaction::where('transaction_code', $uniqueCode)->update(['status' => 'completed']);
            TransactionPayment::where('unique_code', $uniqueCode)->update(['status' => $status]);
            Log::info("Payment with unique code {$uniqueCode} was successful.");
        } elseif ($status === 'Canceled') {
            Transaction::where('transaction_code', $uniqueCode)->update(['status' => 'canceled']);
            TransactionPayment::where('unique_code', $uniqueCode)->update(['status' => $status]);
            Log::info("Payment with unique code {$uniqueCode} was canceled.");
        } else {
            return response()->json(['success' => false, 'message' => 'Unknown status'], 400);
        }

        return response()->json(['success' => true]);
    }
}

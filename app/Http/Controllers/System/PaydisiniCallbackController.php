<?php

namespace App\Http\Controllers\System;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use App\Models\TransactionPayment;
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

        $apiKey = 'XXX';

        // Generate the expected signature
        $expectedSignature = md5($apiKey . $uniqueCode . 'CallbackStatus');

        // Validate the request signature
        if ($signature !== $expectedSignature) {
            return response()->json(['success' => false, 'message' => 'Invalid signature', 'apikey' => $apiKey, 'siganture' => $expectedSignature, 'signature_make' => $signature], 400);
        }

        // Process the payment status
        if ($status === 'Success') {
            // Update the SalesOrder record status to 'completed'
            Transaction::where('transaction_code', $uniqueCode)->update(['status' => 'completed']);
            TransactionPayment::where('unique_code', $uniqueCode)->update(['status' => $status]);
            Log::info("Payment with unique code {$uniqueCode} was successful.");
        } elseif ($status === 'Canceled') {
            // Update the SalesOrder record status to 'cancelled'
            Transaction::where('transaction_code', $uniqueCode)->update(['status' => 'canceled']);
            TransactionPayment::where('unique_code', $uniqueCode)->update(['status' => $status]);
            Log::info("Payment with unique code {$uniqueCode} was canceled.");
        } else {
            return response()->json(['success' => false, 'message' => 'Unknown status'], 400);
        }

        return response()->json(['success' => true]);
    }
}

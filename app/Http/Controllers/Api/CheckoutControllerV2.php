<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\PaymentPendingNotification;
use App\Models\ExtraCredit;
use App\Models\Package;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\TransactionPayment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Xendit\Configuration;
use Xendit\Invoice\CreateInvoiceRequest;
use Xendit\Invoice\InvoiceApi;

class CheckoutControllerV2 extends Controller
{
    public function placeOrder(Request $request)
    {
        Configuration::setXenditKey(env('XENDIT_SECRET_KEY'));

        $request->validate([
            'item_type' => 'required|string|in:PACKAGE,CREDIT',
            'item_id' => 'required|integer',
        ]);

        $itemType = $request->input('item_type');
        $itemId = $request->input('item_id');

        // Begin transaction
        DB::beginTransaction();

        try {
            if ($itemType === 'PACKAGE') {
                $item = Package::find($itemId);
            } else {
                $item = ExtraCredit::find($itemId);
            }

            if (!$item) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Item not found',
                    'data' => null,
                ], 404);
            }

            $existingTransaction = Transaction::where('id_user', auth()->id())
                ->whereHas('details', function ($query) use ($itemType, $itemId) {
                    $query->where('item_type', $itemType)
                        ->where('item_id', $itemId);
                })
                ->where('status', 'pending')
                ->first();

            if ($existingTransaction) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Kamu sudah pernah melalukan transaksi & belum terbayar untuk pesanan ini',
                    'data' => $existingTransaction
                ], 400);
            }

            // Calculate total amount
            $amountSub = $item->price;
            $amountTotal = $amountSub;

            $user = $request->user();
            $unique_code = 'BR-' . now()->format('ymd') . '-' . str_pad(random_int(0, 999999999), 9, '0', STR_PAD_LEFT);

            // Create Invoice
            $items = [
                [
                    'name' =>  $item->name,
                    'quantity' => 1,
                    'price' => $amountTotal,
                ]
            ];

            $invoiceCustomerData = [
                'given_names' => $user->name,
                'email' => $user->email,
            ];

            $notificationPreference = [
                'invoice_created' => ['email'],
                'invoice_reminder' => ['email'],
                'invoice_paid' => ['email'],
            ];

            $description = 'Pembayaran ' . $item->name;

            $redirect_url = env('BRAINYS_MODE') === 'STAGING'
                ? 'https://staging.brainys.oasys.id/order/detail/' . $unique_code
                : 'https://brainys.oasys.id/order/detail/' . $unique_code;

            $createInvoiceRequest = new CreateInvoiceRequest([
                'external_id' => $unique_code,
                'amount' => $amountTotal,
                'items' => $items,
                'description' => $description,
                'invoice_duration' => 86400,
                'customer' => $invoiceCustomerData,
                'customer_notification_preference' => $notificationPreference,
                'success_redirect_url' => $redirect_url,
            ]);

            $apiInstance        = new InvoiceApi();
            $generateInvoice    = $apiInstance->createInvoice($createInvoiceRequest);
            $responseData       = $generateInvoice;

            // Check transaction
            if ($generateInvoice) {
                if (isset($responseData['id'])) {
                    $paymentArray['success']                        = true;
                    $paymentArray['id']                             = $responseData['id'] ?? '';
                    $paymentArray['external_id']                    = $responseData['external_id'] ?? '';
                    $paymentArray['user_id']                        = $responseData['user_id'] ?? '';
                    $paymentArray['status']                         = $responseData['status'] ?? '';
                    $paymentArray['merchant_name']                  = $responseData['merchant_name'] ?? '';
                    $paymentArray['merchant_profile_picture_url']   = $responseData['merchant_profile_picture_url'] ?? '';
                    $paymentArray['amount']                         = $responseData['amount'] ?? 0;
                    $paymentArray['description']                    = $responseData['description'] ?? '';
                    $paymentArray['expiry_date']                    = $responseData['expiry_date'] ?? '';
                    $paymentArray['invoice_url']                    = $responseData['invoice_url'] ?? '';
                }
            } else {
                $status                     = 'pending';
                $paymentArray['success']    = false;
                $paymentArray['msg']        = $responseData['msg'] ?? 'Unknown error';
                $paymentArray['debug']      = $responseData;
            }

            // Create the transaction
            $transaction = Transaction::create([
                'id_user' => auth()->id(),
                'transaction_date' => now(),
                'transaction_code' => $unique_code,
                'transaction_name' => $item->name,
                'amount_sub' => $amountSub,
                'amount_fee' => $paymentArray['data']['fee'] ?? 0,
                'amount_total' => $amountSub + ($paymentArray['data']['fee'] ?? 0),
                'status' => 'pending',
            ]);

            // Create transaction payment
            $transactionPayment = TransactionPayment::create([
                'id_transaction' => $transaction->id,
                'pay_id' => $paymentArray['id'] ?? null,
                'unique_code' => $unique_code,
                'service' => '-',
                'service_name' => '-',
                'amount' => $paymentArray['amount'] ?? 0,
                'balance' => null,
                'fee' => null,
                'type_fee' => null,
                'status' => $paymentArray['status'] ?? null,
                'expired' => isset($paymentArray['expiry_date'])
                    ? Carbon::parse($paymentArray['expiry_date'])->setTimezone('Asia/Jakarta')
                    : null,
                'qrcode_url' => null,
                'virtual_account' => null,
                'checkout_url' => $paymentArray['invoice_url'] ?? null,
                'checkout_url_v2' => null,
                'checkout_url_v3' => null,
                'checkout_url_beta' => null,
            ]);


            // Create transaction detail
            TransactionDetail::create([
                'id_transaction' => $transaction->id,
                'item_type' => $itemType,
                'item_id' => $itemId,
                'item_price' => $item->price,
                'item_qty' => 1,
            ]);

            // Commit the transaction
            DB::commit();

            //Mail
            $user = User::where('id', $transaction->id_user)->first();
            Mail::to($user->email)->send(new PaymentPendingNotification($user, $transaction, $transactionPayment, null));

            return response()->json([
                'status' => 'success',
                'message' => 'Order placed successfully',
                'data' => [
                    'transaction' => $transaction,
                    'transaction_payment' => $transactionPayment,
                ],
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json([
                'status' => 'error',
                'message' => 'Failed to place order: ' . $e->getMessage(),
                'data' => null,
                'payment' => $responseData
            ], 500);
        }
    }
}

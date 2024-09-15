<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Mail\PaymentPendingNotification;
use App\Models\Package;
use App\Models\ExtraCredit;
use App\Models\User;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\TransactionPayment;
use App\Services\PaydisiniService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Mail;

class CheckoutController extends Controller
{
    public function getInfo(Request $request)
    {
        $request->validate([
            'item_type' => 'required|string|in:PACKAGE,CREDIT',
            'item_id' => 'required|integer'
        ]);

        $itemType = $request->input('item_type');
        $itemId = $request->input('item_id');

        if ($itemType === 'PACKAGE') {
            $item = Package::find($itemId);
        } elseif ($itemType === 'CREDIT') {
            $item = ExtraCredit::find($itemId);
        } else {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid item type',
                'data' => null,
            ], 400);
        }

        if (!$item) {
            return response()->json([
                'status' => 'error',
                'message' => 'Item not found',
                'data' => null,
            ], 404);
        }

        // Ambil payment methods dengan status true dan kelompokkan berdasarkan kategori
        $paymentMethods = PaymentMethod::where('status', true)->get()->groupBy('category')->map(function ($group, $category) use ($item) {
            // Jika kategori virtual_account dan harga item kurang dari 10.000, return array kosong
            if ($category === 'virtual_account' && $item->price < 10000) {
                return [];
            }

            return $group->map(function ($paymentMethod) {
                return [
                    'id' => $paymentMethod->id,
                    'thumbnail' => url('storage/' . $paymentMethod->thumbnail),
                    'name' => $paymentMethod->name,
                    'code' => $paymentMethod->code,
                ];
            });
        });

        return response()->json([
            'status' => 'success',
            'message' => 'Item retrieved successfully',
            'data' => [
                'items' => [
                    'item_id' => $item->id,
                    'item_type' => $itemType,
                    'item_model' => get_class($item),
                    'item_name' => $item->name,
                    'item_price' => $item->price,
                ],
                'payment_method' => $paymentMethods,
            ],
        ]);
    }

    public function placeOrder(Request $request)
    {
        $request->validate([
            'item_type' => 'required|string|in:PACKAGE,CREDIT',
            'item_id' => 'required|integer',
            'payment_method_id' => 'required|integer',
        ]);

        $itemType = $request->input('item_type');
        $itemId = $request->input('item_id');
        $paymentMethodId = $request->input('payment_method_id');



        // Begin transaction
        DB::beginTransaction();

        try {
            // Find the item based on the item_type and item_id
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

            // Find the payment method
            $paymentMethod = PaymentMethod::find($paymentMethodId);

            if (!$paymentMethod || !$paymentMethod->status) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid payment method',
                    'data' => null,
                ], 400);
            }

            // Calculate total amount (this example assumes no discounts or additional fees)
            $amountSub = $item->price;
            // $amountFee = $paymentMethod->fee;
            $amountTotal = $amountSub;

            $unique_code = 'BR-' . now()->format('ymd') . '-' . str_pad(random_int(0, 999999999), 9, '0', STR_PAD_LEFT);

            $paydisini = new PaydisiniService(env('PAYDISINI_KEY'), env('PAYDISINI_ID'));

            // Menyiapkan data untuk transaksi baru
            $transactionData = [
                'unique_code' => $unique_code,
                'service' => $paymentMethod->provider_code,
                'amount' => $amountTotal,
                'note' => '-',
                'valid_time' => 86400,
                'ewallet_phone' => null,
                'customer_email' => null,
                'type_fee' => 1,
                'payment_guide' => 'TRUE',
                'callback_count' => 3,
                'return_url' => 'https://brainys.oasys.id/dashboard'
            ];

            // Create new transaction payment
            $transactionResponse    = $paydisini->createNewTransaction($transactionData);
            $responseData           = $transactionResponse;

            // dd($transactionResponse);

            // Check transaction
            if ($responseData['success']) {
                if (isset($responseData['data']['pay_id'])) {
                    $paymentArray['success']                    = true;
                    $paymentArray['data']['pay_id']             = $responseData['data']['pay_id'] ?? '';
                    $paymentArray['data']['unique_code']        = $responseData['data']['unique_code'] ?? '';
                    $paymentArray['data']['service']            = $responseData['data']['service'] ?? '';
                    $paymentArray['data']['service_name']       = $responseData['data']['service_name'] ?? '';
                    $paymentArray['data']['amount']             = $responseData['data']['amount'] ?? 0;
                    $paymentArray['data']['balance']            = $responseData['data']['balance'] ?? 0;
                    $paymentArray['data']['fee']                = $responseData['data']['fee'] ?? 0;
                    $paymentArray['data']['type_fee']           = $responseData['data']['type_fee'] ?? '';
                    $paymentArray['data']['note']               = $responseData['data']['note'] ?? '';
                    $paymentArray['data']['status']             = $responseData['data']['status'] ?? '';
                    $paymentArray['data']['expired']            = $responseData['data']['expired'] ?? '';
                    $paymentArray['data']['checkout_url']       = $responseData['data']['checkout_url'] ?? '';
                    $paymentArray['data']['checkout_url_v1']    = $responseData['data']['checkout_url_v1'] ?? '';
                    $paymentArray['data']['checkout_url_v2']    = $responseData['data']['checkout_url_v2'] ?? '';
                    $paymentArray['data']['checkout_url_v3']    = $responseData['data']['checkout_url_v3'] ?? '';
                    $paymentArray['data']['checkout_url_beta']  = $responseData['data']['checkout_url_beta'] ?? '';
                }

                if (isset($responseData['data']['qrcode_url'])) {
                    $paymentArray['data']['type']       = 'qris';
                    $paymentArray['data']['qrcode_url'] = $responseData['data']['qrcode_url'] ?? '';
                    $paymentArray['data']['qr_content'] = $responseData['data']['qr_content'] ?? '';
                }

                if (isset($responseData['data']['virtual_account'])) {
                    $paymentArray['data']['type']               = 'va';
                    $paymentArray['data']['virtual_account']    = $responseData['data']['virtual_account'] ?? '';
                }
            } else {
                $status                     = 'pending';
                $paymentArray['success']    = false;
                $paymentArray['msg']        = $responseData['msg'] ?? 'Unknown error';
                $paymentArray['debug']      = $paymentMethod;
            }

            // Create the transaction
            $transaction = Transaction::create([
                'id_user' => auth()->id(),
                'transaction_date' => now(),
                'transaction_code' => $unique_code,
                'transaction_name' => $item->name,
                'amount_sub' => $amountSub,
                'amount_fee' => $paymentArray['data']['fee'],
                'amount_total' => $amountSub + $paymentArray['data']['fee'],
                'status' => 'pending',
            ]);

            // Create transaction payment
            $transactionPayment = TransactionPayment::create([
                'id_transaction' => $transaction->id,
                'pay_id' => $paymentArray['data']['pay_id'] ?? null,
                'unique_code' => $paymentArray['data']['unique_code'] ?? null,
                'service' => $paymentArray['data']['service'] ?? null,
                'service_name' => $paymentArray['data']['service_name'] ?? null,
                'amount' => $paymentArray['data']['amount'] ?? 0,
                'balance' => $paymentArray['data']['balance'] ?? 0,
                'fee' => $paymentArray['data']['fee'] ?? 0,
                'type_fee' => $paymentArray['data']['type_fee'] ?? null,
                'status' => $paymentArray['data']['status'] ?? null,
                'expired' => $paymentArray['data']['expired'] ?? null,
                'qrcode_url' => $paymentArray['data']['qrcode_url'] ?? null,
                'virtual_account' => $paymentArray['data']['virtual_account'] ?? null,
                'checkout_url' => $paymentArray['data']['checkout_url'] ?? null,
                'checkout_url_v2' => $paymentArray['data']['checkout_url_v2'] ?? null,
                'checkout_url_v3' => $paymentArray['data']['checkout_url_v3'] ?? null,
                'checkout_url_beta' => $paymentArray['data']['checkout_url_beta'] ?? null,
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
            Mail::to($user->email)->send(new PaymentPendingNotification($user, $transaction, $transactionPayment, $paymentMethod));

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

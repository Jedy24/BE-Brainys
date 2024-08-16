<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Package;
use App\Models\ExtraCredit;
use App\Models\PaymentMethod;
use App\Models\Transaction;
use App\Models\TransactionDetail;
use App\Models\TransactionPayment;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

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
        $paymentMethods = PaymentMethod::where('status', true)->get()->groupBy('category')->map(function ($group) {
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
            $amountFee = $paymentMethod->fee;
            $amountTotal = $amountSub + $amountFee;

            // Create the transaction
            $transaction = Transaction::create([
                'id_user' => auth()->id(),
                'transaction_date' => now(),
                'transaction_code' => uniqid('TX-'),
                'transaction_name' => $item->name,
                'amount_sub' => $amountSub,
                'amount_fee' => $amountFee,
                'amount_total' => $amountTotal,
                'status' => 'pending',
            ]);

            // Create transaction payment
            $transactionPayment = TransactionPayment::create([
                'id_transaction' => $transaction->id,
                'pay_id' => uniqid('PAY-'),
                // 'unique_code' => strtoupper(str_random(10)),
                'service' => $paymentMethod->code,
                'service_name' => $paymentMethod->name,
                'amount' => $amountTotal,
                'balance' => $amountSub,
                'fee' => $amountFee,
                'type_fee' => $paymentMethod->type_fee,
                'status' => 'pending',
                'expired' => now()->addHours(2),
                'qrcode_url' => $paymentMethod->qrcode_url,
                'virtual_account' => $paymentMethod->virtual_account,
                'checkout_url' => $paymentMethod->checkout_url,
                'checkout_url_v2' => $paymentMethod->checkout_url_v2,
                'checkout_url_v3' => $paymentMethod->checkout_url_v3,
                'checkout_url_beta' => $paymentMethod->checkout_url_beta,
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
            ], 500);
        }
    }
}

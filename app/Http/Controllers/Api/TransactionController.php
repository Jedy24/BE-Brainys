<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class TransactionController extends Controller
{
    public function index(Request $request)
    {
        try {
            $userId = Auth::id();

            $transactions = Transaction::with('details')
                ->where('id_user', $userId)
                ->orderBy('created_at', 'desc')
                ->paginate(10);

            $transactions->getCollection()->transform(function ($transaction) {
                $transaction->amount_sub = intval($transaction->amount_sub);
                $transaction->amount_fee = intval($transaction->amount_fee);
                $transaction->amount_total = intval($transaction->amount_total);
                
                $transaction->details->transform(function ($detail) {
                    return [
                        'item_type' => $detail->item_type,
                        'item_id' => $detail->item_id,
                        'item_price' => intval($detail->item_price),
                        'item_qty' => $detail->item_qty,
                    ];
                });

                return $transaction;
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Transactions retrieved successfully',
                'data' => $transactions,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve transactions: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }
}

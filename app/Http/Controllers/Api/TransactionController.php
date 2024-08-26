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
                ->get();

            $transactions->transform(function ($transaction) {
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

            $perPage = 8;
            $page = request('page', 1);
            $pagedData = $transactions->slice(($page - 1) * $perPage, $perPage)->values();

            $urlPrefix = 'https://be.brainys.oasys.id/api/';
            $pagedData = $pagedData->map(function ($item) use ($urlPrefix) {
                // $item['url_api_data'] = $urlPrefix . $item['type'] . '/subscription/history/' . $item['id'];
                return $item;
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Transactions retrieved successfully',
                'data' => $pagedData,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $transactions->count(),
                    'last_page' => ceil($transactions->count() / $perPage),
                ]
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

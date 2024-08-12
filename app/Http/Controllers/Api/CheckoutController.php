<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Package;
use App\Models\ExtraCredit;
use App\Models\PaymentMethod;
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
}

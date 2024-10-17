<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\UserPackage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Carbon\Carbon;

class SubscriptionController extends Controller
{
    /**
     * Cancel the subscription of the authenticated user's package.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function cancelSubscription()
    {
        // Get the authenticated user
        $user = Auth::user();

        // Find the first user package for the authenticated user
        $userPackage = UserPackage::where('id_user', $user->id)
                                  ->with('package')
                                  ->first();

        // If no package is found for the user
        if (!$userPackage) {
            return response()->json([
                'status' => 'error',
                'message' => 'Langganan tidak ditemukan.',
                'data' => null
            ], 404);
        }

        // Check if package type is 'free', free packages cannot be cancelled
        if ($userPackage->package->type === 'free') {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak bisa membatalkan paket gratis.',
                'data' => null
            ], 400);
        }

        // Check if the subscription is already non-renewable
        if ($userPackage->is_renewable === false || $userPackage->is_renewable === 0) {
            return response()->json([
                'status' => 'error',
                'message' => 'Langganan sudah dibatalkan sebelumnya.',
                'data' => null
            ], 400);
        }

        // Check if expired_at is less than 7 days away
        $now = Carbon::now();
        if ($userPackage->expired_at && $userPackage->expired_at->diffInDays($now) < 7) {
            return response()->json([
                'status' => 'error',
                'message' => 'Tidak bisa membatalkan langganan dengan sisa waktu kurang dari 7 hari.',
                'data' => null
            ], 400);
        }

        // If conditions are met, set is_renewable to false and save
        $userPackage->is_renewable = false;
        $userPackage->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Langganan berhasil dibatalkan.',
            'data' => $userPackage
        ], 200);
    }
}

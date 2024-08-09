<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExtraCredit;
use Illuminate\Http\Request;

class ExtraCreditController extends Controller
{
    /**
     * Get extra credit data.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getExtraCredit()
    {
        try {
            $extraCredits = ExtraCredit::select('id', 'name', 'credit_amount', 'price')->get();

            if ($extraCredits->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No extra credits found',
                    'data' => null,
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Extra credits retrieved successfully',
                'data' => $extraCredits,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve extra credits: ' . $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Service;

use App\Http\Controllers\Controller;
use App\Models\ModuleCreditCharge;
use Illuminate\Http\Request;

class ModuleCreditChargeController extends Controller
{
    /**
     * Get all ModuleCreditCharge data.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAllData()
    {
        try {
            $moduleCreditCharges = ModuleCreditCharge::all();

            return response()->json([
                'status' => 'success',
                'data' => $moduleCreditCharges
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve data.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get one ModuleCreditCharge data by slug.
     *
     * @param  string  $slug
     * @return \Illuminate\Http\JsonResponse
     */
    public function getDataBySlug($slug)
    {
        try {
            $moduleCreditCharge = ModuleCreditCharge::where('slug', $slug)->first();

            if (!$moduleCreditCharge) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data not found.'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $moduleCreditCharge
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to retrieve data.',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}

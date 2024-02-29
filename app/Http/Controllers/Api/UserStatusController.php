<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\MaterialHistories;

class UserStatusController extends Controller
{
    public function getStatus(Request $request)
    {
        try {
            // Retrieve the authenticated user
            $user = $request->user();

            // Get the user's material usage status
            $materialLimit = $user->limit_generate_material;
            $usedMaterials = MaterialHistories::where('user_id', $user->id)->count();

            // Construct the status data
            $status = [
                'materials' => [
                    'limit' => $materialLimit,
                    'used' => $usedMaterials,
                ],
                'syllabus' => [
                    'limit' => 0,
                    'used' => 0,
                ],
                'exercise' => [
                    'limit' => 0,
                    'used' => 0,
                ]
            ];

            // Return the response with user status data
            return response()->json([
                'status' => 'success',
                'message' => 'User status retrieved successfully',
                'data' => $status,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExerciseHistories;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\MaterialHistories;
use App\Models\SyllabusHistories;

class UserStatusController extends Controller
{
    public function getStatus(Request $request)
    {
        try {
            // Retrieve the authenticated user
            $user = $request->user();

            // Construct the status data
            $status = [
                'materials' => [
                    'limit' => $user->limit_generate_material,
                    'used' => MaterialHistories::where('user_id', $user->id)->count(),
                ],
                'syllabus' => [
                    'limit' => $user->limit_generate_syllabus,
                    'used' => SyllabusHistories::where('user_id', $user->id)->count(),
                ],
                'exercise' => [
                    'limit' => $user->limit_generate_exercise,
                    'used' => ExerciseHistories::where('user_id', $user->id)->count(),
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

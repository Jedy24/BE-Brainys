<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExerciseHistories;
use Illuminate\Http\Request;
use App\Models\User;
use App\Models\MaterialHistories;
use App\Models\SyllabusHistories;
use App\Models\UserNotification;

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
                    // 'limit' => $user->limit_generate_material,
                    'used' => MaterialHistories::where('user_id', $user->id)->count(),
                ],
                'syllabus' => [
                    // 'limit' => $user->limit_generate_syllabus,
                    'used' => SyllabusHistories::where('user_id', $user->id)->count(),
                ],
                'exercise' => [
                    // 'limit' => $user->limit_generate_exercise,
                    'used' => ExerciseHistories::where('user_id', $user->id)->count(),
                ],
                'all' => [
                    'limit' => $user->limit_generate,
                    'used' => $user->generateAllSum(),
                ]
            ];

            // Initialize notification message
            $notificationMessage = null;

            // Check if user is approaching the generate limit
            if ($status['all']['used'] >= 15 && $status['all']['used'] < 20) {
                // Send notification directly
                $this->sendNotification($user, 'Anda hampir mencapai limit untuk melakukan generasi.');
                // Set notification message
                $notificationMessage = 'Anda hampir mencapai limit untuk melakukan generasi.';
            }

            // Add notification message to status data
            $status['notification'] = $notificationMessage;

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

    private function sendNotification(User $user, string $message)
    {
        $notification = new UserNotification([
            'user_id' => $user->id,
            'message' => $message,
        ]);
        $notification->save();
    }
}

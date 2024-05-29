<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\UserInvitation;
use App\Notifications\InvitationNotification;

class SendInvitationController extends Controller
{
    public function sendInvitation(Request $request){
        $request->validate([
            'email' => 'required|email',
        ]);

        $invitation = UserInvitation::where('email', $request->email)
                                    ->where('is_used', false)
                                    ->where('expired_at', '>', now())
                                    ->first();

        if (!$invitation) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invitation not found or expired'
            ], 404);
        }

        try {
            $invitation->notify(new InvitationNotification($invitation->invite_code));
            return response()->json([
                'status' => 'success',
                'message' => 'Invitation sent successfully']);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send invitation', 'error' => $e->getMessage()
            ], 500);
        }
    }
}

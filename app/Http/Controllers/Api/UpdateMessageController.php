<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Models\UpdateMessage;

class UpdateMessageController extends Controller
{
    public function checkUpdates(Request $request)
    {
        $updateMessages = UpdateMessage::all();

        $updates = [];

        foreach ($updateMessages as $updateMessage) {
            $updates[] = [
                'id' => $updateMessage->id,
                'version' => $updateMessage->version,
                'update_message' => $updateMessage->message,
            ];
        }

        return response()->json([
            'status' => 'success',
            'message' => 'All update messages retrieved successfully',
            'updates' => $updates,
        ]);
    }

    public function showUpdates(Request $request, $id)
    {
        $updateMessage = UpdateMessage::find($id);

        if (!$updateMessage) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        $update = [
            'id' => $updateMessage->id,
            'version' => $updateMessage->version,
            'update_message' => $updateMessage->message,
        ];

        return response()->json([
            'status' => 'success',
            'message' => 'Data retrieved successfully',
            'update' => $update,
        ]);
    }
}

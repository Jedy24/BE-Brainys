<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\UpdateMessage;

class UpdateMessageController extends Controller
{
    public function checkUpdates(Request $request)
    {
        // Dapatkan input pesan pembaharuan dari pengguna
        $version = $request->input('version');
        $updateMessage = $request->input('update_message');

        // Simpan pesan pembaharuan ke dalam database
        $newUpdateMessage = UpdateMessage::create([
            'version' => $version,
            'message' => $updateMessage
        ]);

        // Simpan informasi pembaharuan dalam array
        $updates = [
            [
                'version' => $version,
                'update_message' => $updateMessage,
            ]
            // Tambahkan informasi pembaharuan lainnya jika diperlukan
        ];

        // Return respons JSON dengan informasi pembaharuan
        return response()->json([
            'status' => 'success',
            'message' => 'Update information retrieved successfully',
            'updates' => $updates,
        ]);
    }
}

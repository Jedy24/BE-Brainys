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
        // Menghapus seluruh data pada tabel update_messages
        DB::table('update_messages')->truncate();

        // Ambil pesan pembaharuan dari database admin_brainys
        $updateMessages = DB::connection('admin_brainys')->table('update_messages')->get();

        // Siapkan array untuk menyimpan informasi pembaharuan
        $updates = [];

        // Loop melalui setiap pesan pembaharuan dan tambahkan ke dalam array
        foreach ($updateMessages as $updateMessage) {
            $newUpdateMessage = UpdateMessage::create([
                'version' => $updateMessage->version,
                'message' => $updateMessage->message
            ]);

            // Tambahkan informasi pembaharuan ke array updates
            $updates[] = [
                'id' => $newUpdateMessage->id,
                'version' => $updateMessage->version,
                'update_message' => $updateMessage->message,
            ];
        }

        // Return respons JSON dengan informasi pembaharuan
        return response()->json([
            'status' => 'success',
            'message' => 'Update information retrieved and saved successfully',
            'updates' => $updates,
        ]);
    }

    public function showUpdates(Request $request, $id)
    {
        // Ambil data berdasarkan id
        $updateMessages = UpdateMessage::find($id);

        // Jika tidak ada data, kirim respons JSON dengan pesan "Tidak ada data"
        if (!$updateMessages) {
            return response()->json([
                'status' => 'error',
                'message' => 'Data tidak ditemukan',
            ], 404);
        }

        // Respons JSON dengan informasi pesan pembaharuan
        $updates = [
            'id' => $updateMessages->id,
            'version' => $updateMessages->version,
            'update_message' => $updateMessages->message,
        ];

        // Return respons JSON dengan informasi pembaharuan
        return response()->json([
            'status' => 'success',
            'message' => 'Data retrieved successfully',
            'updates' => $updates,
        ]);
    }
}

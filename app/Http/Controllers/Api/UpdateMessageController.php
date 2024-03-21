<?php

namespace App\Http\Controllers\api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

use App\Models\UpdateMessage;

class UpdateMessageController extends Controller
{
    public function checkUpdates(Request $request)
    {
        // Cek apakah ada data terbaru pada cache
        if (Cache::has('latest_version')) {
            $latestVersion = Cache::get('latest_version');
        } else {
            // Jika tidak ada data terbaru di cache, ambil data dari database admin_brainys
            $latestVersion = DB::connection('admin_brainys')->table('update_messages')->max('version');

            // Simpan data terbaru ke dalam cache
            Cache::put('latest_version', $latestVersion);
        }

        // Ambil pesan pembaharuan dari database admin_brainys berdasarkan data terbaru
        $updateMessages = DB::connection('admin_brainys')->table('update_messages')
            ->where('version', '>', $latestVersion)
            ->get();

        // Siapkan array untuk menyimpan informasi pembaharuan
        $updates = [];

        // Looping melalui setiap pesan pembaharuan dan tambahkan ke dalam array
        foreach ($updateMessages as $updateMessage) {
            $newUpdateMessage = UpdateMessage::create([
                'version' => $updateMessage->version,
                'message' => $updateMessage->message
            ]);

            // Tambahkan informasi pembaharuan ke array updates
            $updates[] = [
                'version' => $updateMessage->version,
                'update_message' => $updateMessage->message,
            ];
        }

        // Cek apakah terdapat data pembaharuan baru
        if (empty($updates)) {
            return response()->json([
                'status' => 'success',
                'message' => 'Tidak ada data baru yang tersedia',
                'updates' => $updates,
            ]);
        }

        // Return respons JSON dengan informasi pembaharuan
        return response()->json([
            'status' => 'success',
            'message' => 'Update information retrieved and saved successfully',
            'updates' => $updates,
        ]);
    }
}

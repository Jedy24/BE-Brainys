<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExerciseHistories;
use App\Models\MaterialHistories;
use App\Models\SyllabusHistories;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class HistoryController extends Controller
{
    public function showHistory()
    {
        try {
            // Retrieve syllabus history records
            $syllabusHistories = SyllabusHistories::select([
                'id',
                'subject AS name',
                'notes AS description',
                DB::raw("'syllabus' AS type"),
                'created_at',
                DB::raw("DATE_FORMAT(created_at, '%d %b %Y | %H:%i') AS created_at_format"),
            ])->get();

            // Retrieve material history records
            $materialHistories = MaterialHistories::select([
                'id',
                'name',
                'notes AS description',
                DB::raw("'material' AS type"),
                'created_at',
                DB::raw("DATE_FORMAT(created_at, '%d %b %Y | %H:%i') AS created_at_format"),
            ])->get();

            // Retrieve exercise history records
            $exerciseHistories = ExerciseHistories::select([
                'id',
                'name',
                'notes AS description',
                DB::raw("'exercise' AS type"),
                'created_at',
                DB::raw("DATE_FORMAT(created_at, '%d %b %Y | %H:%i') AS created_at_format"),
            ])->get();

            // Menggabungkan semua riwayat ke dalam satu koleksi
            $history = $syllabusHistories->merge($materialHistories)->merge($exerciseHistories);

            // Sort the merged collection by created_at in descending order
            $sortedHistory = $history->sortByDesc('created_at');

            // Mengonversi koleksi menjadi array tanpa kunci
            $historyArray = $sortedHistory->values()->all();

            // Generate URL for each item
            foreach ($historyArray as &$item) {
                $urlPrefix = 'https://be.brainys.oasys.id/api/';
                $item['url_api_data'] = $urlPrefix . $item['type'] . '/history/' . $item['id'];
                // Assuming the base URL for word download is 'http://example.com/api/download/'
                // $item['url_word_download'] = "http://example.com/api/download/{$item['type']}/{$item['id']}";
            }

            // Return the response with the sorted history data
            return response()->json([
                'status' => 'success',
                'message' => 'History retrieved successfully',
                'data' => $historyArray,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage(),
                'data' => null,
            ], 500);
        }
    }

    public function showHistoryFilter(Request $request)
    {
        try {
            // Validasi input
            $request->validate([
                'type' => 'required|in:syllabus,material,exercise',
            ]);

            // Retrieve the type from the request
            $type = $request->input('type');

            // Initialize variable to hold history data
            $histories = [];

            // Retrieve history records based on the type
            switch ($type) {
                case 'syllabus':
                    $histories = SyllabusHistories::orderByDesc('created_at')->get();
                    break;
                case 'material':
                    $histories = MaterialHistories::orderByDesc('created_at')->get();
                    break;
                case 'exercise':
                    $histories = ExerciseHistories::orderByDesc('created_at')->get();
                    break;
                default:
                    throw new \Exception('Invalid history type provided.');
            }

            // Prepare the response data
            $responseData = $histories->map(function ($history) use ($type) {
                return [
                    'id' => $history->id,
                    'name' => ($type === 'syllabus' ? $history->subject : $history->name),
                    'description' => $history->notes,
                    'type' => $type,
                    'created_at' => $history->created_at->format('d M Y | H:i'),
                    'url_api_data' => 'https://be.brainys.oasys.id/api/' . $type . '/history/' . $history->id,
                ];
            });

            // Return the response
            return response()->json([
                'status' => 'success',
                'message' => 'History filtered successfully',
                'data' => $responseData,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage(),
                'data' => null,
            ], 400);
        }
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExerciseHistories;
use App\Models\MaterialHistories;
use App\Models\SyllabusHistories;
use App\Models\HintHistories;
use App\Models\BahanAjarHistories;
use App\Models\GamificationHistories;
use App\Models\AlurTujuanPembelajaranHistories;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HistoryController extends Controller
{
    public function showHistory()
    {
        $user_id = Auth::id();

        try {
            // Retrieve all types of history records for the specific user
            $histories = collect();

            $histories = $histories->concat(SyllabusHistories::where('user_id', $user_id)
                ->select([
                    'id',
                    'subject AS name',
                    'notes AS description',
                    DB::raw("'syllabus' AS type"),
                    'created_at',
                    DB::raw("DATE_FORMAT(created_at, '%d %b %Y | %H:%i') AS created_at_format"),
                ])->get());

            $histories = $histories->concat(MaterialHistories::where('user_id', $user_id)
                ->select([
                    'id',
                    'name',
                    'notes AS description',
                    DB::raw("'material' AS type"),
                    'created_at',
                    DB::raw("DATE_FORMAT(created_at, '%d %b %Y | %H:%i') AS created_at_format"),
                ])->get());

            $histories = $histories->concat(ExerciseHistories::where('user_id', $user_id)
                ->select([
                    'id',
                    'name',
                    'notes AS description',
                    DB::raw("'exercise' AS type"),
                    'created_at',
                    DB::raw("DATE_FORMAT(created_at, '%d %b %Y | %H:%i') AS created_at_format"),
                ])->get());

            $histories = $histories->concat(BahanAjarHistories::where('user_id', $user_id)
                ->select([
                    'id',
                    'name',
                    'notes AS description',
                    DB::raw("'bahan-ajar' AS type"),
                    'created_at',
                    DB::raw("DATE_FORMAT(created_at, '%d %b %Y | %H:%i') AS created_at_format"),
                ])->get());

            $histories = $histories->concat(GamificationHistories::where('user_id', $user_id)
                ->select([
                    'id',
                    'name',
                    'notes AS description',
                    DB::raw("'gamification' AS type"),
                    'created_at',
                    DB::raw("DATE_FORMAT(created_at, '%d %b %Y | %H:%i') AS created_at_format"),
                ])->get());

            $histories = $histories->concat(HintHistories::where('user_id', $user_id)
                ->select([
                    'id',
                    'name',
                    'notes AS description',
                    DB::raw("'hint' AS type"),
                    'created_at',
                    DB::raw("DATE_FORMAT(created_at, '%d %b %Y | %H:%i') AS created_at_format"),
                ])->get());

            $histories = $histories->concat(AlurTujuanPembelajaranHistories::where('user_id', $user_id)
                ->select([
                    'id',
                    'name',
                    'notes AS description',
                    DB::raw("'atp' AS type"),
                    'created_at',
                    DB::raw("DATE_FORMAT(created_at, '%d %b %Y | %H:%i') AS created_at_format"),
                ])->get());

            // Sort the merged collection by created_at in descending order
            $sortedHistory = $histories->sortByDesc('created_at');

            // Paginate the results
            $perPage = 8;
            $page = request('page', 1);
            $pagedData = $sortedHistory->slice(($page - 1) * $perPage, $perPage)->values();

            // Generate URL for each item
            $urlPrefix = 'https://be.brainys.oasys.id/api/';
            $pagedData = $pagedData->map(function ($item) use ($urlPrefix) {
                $item['url_api_data'] = $urlPrefix . $item['type'] . '/history/' . $item['id'];
                return $item;
            });

            // Return the response with the paginated history data
            return response()->json([
                'status' => 'success',
                'message' => 'History retrieved successfully',
                'data' => $pagedData,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $sortedHistory->count(),
                    'last_page' => ceil($sortedHistory->count() / $perPage),
                ]
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
                'type' => 'required|in:syllabus,material,exercise,atp,bahan-ajar,gamification,hint',
                'page' => 'integer|min:1'
            ]);

            // Retrieve the type from the request
            $type = $request->input('type');
            $perPage = 8;
            $page = $request->input('page', 1);
            $user_id = Auth::id();

            // Initialize variable to hold history data
            $histories = [];

            // Retrieve history records based on the type and user_id
            switch ($type) {
                case 'syllabus':
                    $histories = SyllabusHistories::where('user_id', $user_id)
                        ->orderByDesc('created_at')->paginate($perPage, ['*'], 'page', $page);
                    break;
                case 'material':
                    $histories = MaterialHistories::where('user_id', $user_id)
                        ->orderByDesc('created_at')->paginate($perPage, ['*'], 'page', $page);
                    break;
                case 'exercise':
                    $histories = ExerciseHistories::where('user_id', $user_id)
                        ->orderByDesc('created_at')->paginate($perPage, ['*'], 'page', $page);
                    break;
                case 'atp':
                    $histories = AlurTujuanPembelajaranHistories::where('user_id', $user_id)
                        ->orderByDesc('created_at')->paginate($perPage, ['*'], 'page', $page);
                    break;
                case 'bahan-ajar':
                    $histories = BahanAjarHistories::where('user_id', $user_id)
                        ->orderByDesc('created_at')->paginate($perPage, ['*'], 'page', $page);
                    break;
                case 'gamification':
                    $histories = GamificationHistories::where('user_id', $user_id)
                        ->orderByDesc('created_at')->paginate($perPage, ['*'], 'page', $page);
                    break;
                case 'hint':
                    $histories = HintHistories::where('user_id', $user_id)
                        ->orderByDesc('created_at')->paginate($perPage, ['*'], 'page', $page);
                    break;
                default:
                    throw new \Exception('Invalid history type provided.');
            }

            // Prepare the response data
            $responseData = $histories->getCollection()->map(function ($history) use ($type) {
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
                'pagination' => [
                    'current_page' => $histories->currentPage(),
                    'per_page' => $perPage,
                    'total' => $histories->total(),
                    'last_page' => $histories->lastPage(),
                ]
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

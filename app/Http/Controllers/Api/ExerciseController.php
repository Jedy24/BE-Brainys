<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExerciseHistories;
use App\Services\OpenAIService;
use Illuminate\Http\Request;

class ExerciseController extends Controller
{
    private $openAI;

    public function __construct(OpenAIService $openAI)
    {
        $this->openAI = $openAI;
    }

    public function generateEssay(Request $request)
    {
        try {
            // Validasi input
            $request->validate([
                'name' => 'required|string',
                'subject' => 'required|string',
                'grade' => 'required|string',
                'number_of_questions' => 'required|integer|min:1',
                // 'notes' => 'optional|string',
            ]);

            // Retrieve the authenticated user
            $user = $request->user();

            // Check if the user has less than 20 material histories
            if ($user->exerciseHistory()->count() >= $user->limit_generate_exercise) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Anda telah mencapai batas maksimal untuk riwayat bahan ajar.',
                    'data' => [
                        'generated_num' => $user->exerciseHistory()->count(),
                        'limit_num' => $user->limit_generate_exercise,
                    ],
                ], 400);
            }

            // Ambil data dari permintaan
            $exerciseName = $request->input('name');
            $mataPelajaran = $request->input('subject');
            $tingkatKelas = $request->input('grade');
            $jumlahSoal = $request->input('number_of_questions');
            $notes = $request->input('notes') ? $request->input('notes') : '';

            // Generate prompt untuk essay exercise
            $prompt = $this->openAI->generateExercisesEssayPrompt($mataPelajaran, $tingkatKelas, $jumlahSoal, $notes);

            // Kirim permintaan ke OpenAI
            $resMessage = $this->openAI->sendMessage($prompt);

            // Parse respon dari OpenAI jika diperlukan
            $parsedResponse = json_decode($resMessage, true);

            // Simpan hasil latihan ke database menggunakan metode create
            $exerciseHistory = ExerciseHistories::create([
                'name' => $exerciseName,
                'subject' => $mataPelajaran,
                'grade' => $tingkatKelas,
                'number_of_question' => $jumlahSoal,
                'notes' => $notes,
                'type' => 'essay',
                'output_data' => json_encode($parsedResponse),
                'user_id' => auth()->id(), // Menggunakan ID pengguna yang sedang diotentikasi
            ]);

            $parsedResponse['id'] = $exerciseHistory->id;

            // Return respon JSON sukses
            return response()->json([
                'status' => 'success',
                'message' => 'Latihan esai berhasil dihasilkan',
                'data' => $parsedResponse,
            ], 200);
        } catch (\Exception $e) {
            // Tangani jika terjadi kesalahan
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage(),
                'data' => json_decode($e->getMessage(), true),
            ], 500);
        }
    }

    public function generateChoice(Request $request)
    {
        try {
            // Validasi input
            $request->validate([
                'name' => 'required|string',
                'subject' => 'required|string',
                'grade' => 'required|string',
                'number_of_questions' => 'required|integer|min:1',
                // 'notes' => 'optional|string',
            ]);

            // Retrieve the authenticated user
            $user = $request->user();

            // Check if the user has less than 20 exercise
            if ($user->exerciseHistory()->count() >= $user->limit_generate_exercise) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Anda telah mencapai batas maksimal untuk riwayat bahan ajar.',
                    'data' => [
                        'generated_num' => $user->exerciseHistory()->count(),
                        'limit_num' => $user->limit_generate_exercise,
                    ],
                ], 400);
            }

            // Ambil data dari permintaan
            $exerciseName = $request->input('name');
            $mataPelajaran = $request->input('subject');
            $tingkatKelas = $request->input('grade');
            $jumlahSoal = $request->input('number_of_questions');
            $notes = $request->input('notes') ? $request->input('notes') : '';

            // Generate prompt untuk essay exercise
            $prompt = $this->openAI->generateExercisesChoicePrompt($mataPelajaran, $tingkatKelas, $jumlahSoal, $notes);

            // Kirim permintaan ke OpenAI
            $resMessage = $this->openAI->sendMessage($prompt);

            // Parse respon dari OpenAI jika diperlukan
            $parsedResponse = json_decode($resMessage, true);

            $user = $request->user();
            $parsedResponse['informasi_umum']['nama_latihan'] = $exerciseName;
            $parsedResponse['informasi_umum']['penyusun'] = $user->name;
            $parsedResponse['informasi_umum']['instansi'] = $user->school_name;
            $parsedResponse['informasi_umum']['tahun_penyusunan'] = Date('Y');

            // Simpan hasil latihan ke database menggunakan metode create
            $exerciseHistory = ExerciseHistories::create([
                'name' => $exerciseName,
                'subject' => $mataPelajaran,
                'grade' => $tingkatKelas,
                'number_of_question' => $jumlahSoal,
                'notes' => $notes,
                'type' => 'multiple_choice',
                'output_data' => json_encode($parsedResponse),
                'user_id' => auth()->id(), // Menggunakan ID pengguna yang sedang diotentikasi
            ]);

            $parsedResponse['generated_num'] = count($parsedResponse['soal_pilihan_ganda']);
            $parsedResponse['id'] = $exerciseHistory->id;

            // Return respon JSON sukses
            return response()->json([
                'status' => 'success',
                'message' => 'Latihan pilihan berhasil dihasilkan',
                'data' => $parsedResponse,
            ], 200);
        } catch (\Exception $e) {
            // Tangani jika terjadi kesalahan
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage(),
                'data' => json_decode($e->getMessage(), true),
            ], 500);
        }
    }

    public function history(Request $request)
    {
        try {
            // Retrieve the authenticated user
            $user = $request->user();

            // Get syllabus histories for the authenticated user
            $exerciseHistories = $user->exerciseHistory()
                ->select(['id', 'name', 'subject', 'grade', 'notes', 'created_at', 'updated_at', 'user_id'])
                ->get();

            if ($exerciseHistories->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak ada riwayat soal latihan untuk akun anda!',
                    'data' => [
                        'generated_num' => 0,
                        'items' => [],
                    ],
                ], 200);
            }

            // Calculate the total generated syllabuses by the user
            $generatedNum = $exerciseHistories->count();

            // Return the response with syllabus histories data
            return response()->json([
                'status' => 'success',
                'message' => 'Riwayat soal latihan ditampilkan',
                'data' => [
                    'generated_num' => $generatedNum,
                    'items' => $exerciseHistories,
                ],
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage(),
                'data' => json_decode($e->getMessage(), true),
            ], 500);
        }
    }

    public function historyDetail(Request $request, $id)
    {
        try {
            // Retrieve the authenticated user
            $user = $request->user();

            // Get a specific syllabus history by ID for the authenticated user
            $exerciseHistories = $user->exerciseHistory()->find($id);

            if (!$exerciseHistories) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Riwayat hasil latihan soal tidak tersedia di akun ini!',
                    'data' => null,
                ], 404);
            }

            // Return the response with Material history data
            return response()->json([
                'status' => 'success',
                'message' => 'Exercise history retrieved successfully',
                'data' => $exerciseHistories,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage(),
                'data' => json_decode($e->getMessage(), true),
            ], 500);
        }
    }
}

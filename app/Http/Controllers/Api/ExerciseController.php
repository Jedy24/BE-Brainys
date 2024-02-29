<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
                'subject' => 'required|string',
                'grade' => 'required|string',
                'number_of_questions' => 'required|integer|min:1',
                // 'notes' => 'optional|string',
            ]);

            // Ambil data dari permintaan
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

            // Simpan hasil latihan ke database jika perlu
            // Exercise::create([
            //     'type' => 'essay',
            //     'subject' => $mataPelajaran,
            //     'grade' => $tingkatKelas,
            //     'number_of_questions' => $jumlahSoal,
            //     'prompt' => $prompt,
            //     'response' => $parsedResponse,
            // ]);

            // Konstruksi data respon untuk sukses
            // $responseData = [
            //     'subject' => $mataPelajaran,
            //     'grade' => $tingkatKelas,
            //     'number_of_questions' => $jumlahSoal,
            //     'prompt' => $prompt,
            //     'response' => $parsedResponse,
            // ];

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
}

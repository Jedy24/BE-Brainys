<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ExerciseHistories;
use App\Services\OpenAIService;
use icircle\Template\Docx\DocxTemplate;
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
                'number_of_questions' => 'required|integer|min:1|max:15',
            ]);

            // Retrieve the authenticated user
            $user = $request->user();

            // Check if the user is active
            if ($user->is_active === 0) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'User tidak aktif. Anda tidak dapat membuat latihan soal.',
                ], 400);
            }

            // Check if the user has less than 20 material histories
            if ($user->generateAllSum() >= $user->limit_generate) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Anda telah mencapai batas maksimal untuk riwayat bahan ajar.',
                    'data' => [
                        'generated_all_num' => $user->generateAllSum(),
                        'limit_all_num' => $user->limit_generate
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
                'type' => 'essay',
                'output_data' => $parsedResponse,
                'user_id' => auth()->id(),
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
                'number_of_questions' => 'required|integer|min:1|max:15',
            ]);

            // Retrieve the authenticated user
            $user = $request->user();

            // Check if the user has less than 20 exercise
            if ($user->generateAllSum() >= $user->limit_generate) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Anda telah mencapai batas maksimal untuk riwayat bahan ajar.',
                    'data' => [
                        'generated_all_num' => $user->generateAllSum(),
                        'limit_all_num' => $user->limit_generate
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

            $correct_options = [];

            // Loop through each question
            foreach ($parsedResponse['soal_pilihan_ganda'] as $question) {
                $correct_options[] = $question['correct_option'];
            }

            // Populate the 'kunci_jawaban' array with the array of correct options
            $parsedResponse['kunci_jawaban'] = $correct_options;
            $parsedResponse['generated_num'] = count($parsedResponse['soal_pilihan_ganda']);

            // Simpan hasil latihan ke database menggunakan metode create
            $exerciseHistory = ExerciseHistories::create([
                'name' => $exerciseName,
                'subject' => $mataPelajaran,
                'grade' => $tingkatKelas,
                'number_of_question' => $jumlahSoal,
                'notes' => $notes,
                'type' => 'multiple_choice',
                'output_data' => $parsedResponse,
                'user_id' => auth()->id(), // Menggunakan ID pengguna yang sedang diotentikasi
            ]);

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

    public function convertToWord(Request $request)
    {
        try {
            $exerciseHistoriesId    = $request->input('id');
            $exerciseHistories      = ExerciseHistories::find($exerciseHistoriesId);

            if ($exerciseHistories->type == 'multiple_choice') {
                $templatePath   = public_path('word_template/Soal_Pilihan_Template.docx');
                $docxTemplate   = new DocxTemplate($templatePath);
                $outputPath     = public_path('word_output/Soal_Pilihan_' . auth()->id() . '_' . md5(time() . '' . rand(1000, 9999)) . '.docx');
            } else if ($exerciseHistories->type == 'essay') {
                $templatePath   = public_path('word_template/Soal_Essay_Template.docx');
                $docxTemplate   = new DocxTemplate($templatePath);
                $outputPath     = public_path('word_output/Soal_Essay_' . auth()->id() . '_' . md5(time() . '' . rand(1000, 9999)) . '.docx');
            } else {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Data tidak ditemukan!',
                ], 500);
            }

            $data = $exerciseHistories->output_data;
            $docxTemplate->merge($data, $outputPath, false, false);

            // Assuming the merge operation is successful
            return response()->json([
                'status' => 'success',
                'message' => 'Dokumen word berhasil dibuat',
                'data' => ['output_path' => $outputPath, 'download_url' => url('word_output/' . basename($outputPath))],
            ], 200);
        } catch (\Exception $e) {
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
                ->select(['id', 'name', 'subject', 'grade', 'type', 'notes', 'created_at', 'updated_at', 'user_id'])
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

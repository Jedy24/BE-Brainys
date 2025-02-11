<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CreditLog;
use App\Models\ModuleCreditCharge;
use Illuminate\Http\Request;
use App\Services\OpenAIService;
use App\Models\CapaianPembelajaran;
use App\Models\SyllabusHistories;
use icircle\Template\Docx\DocxTemplate;

class SyllabusController extends Controller
{
    private $openAI;

    public function __construct(OpenAIService $openAI)
    {
        $this->openAI = $openAI;
    }

    public function generate(Request $request)
    {
        try {
            // Input validation
            $request->validate([
                'name' => 'required',
                'grade' => 'required',
                'subject' => 'required',
                'notes' => 'required',
            ]);

            // Retrieve the authenticated user
            $user = $request->user();

            // Check if the user is active
            if ($user->is_active === 0) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'User tidak aktif. Anda tidak dapat membuat silabus.',
                ], 400);
            }

            // Check if the user has less than 20 syllabus histories
            $moduleCredit = ModuleCreditCharge::where('slug', 'silabus')->first();
            $creditCharge = $moduleCredit->credit_charged_generate ?? 1;
            if ($user->credit < $creditCharge) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Anda telah mencapai batas maksimal untuk riwayat silabus.',
                    'data' => [
                        'user_credit' => $user->credit,
                        'generated_all_num' => $user->generateAllSum(),
                        'limit_all_num' => $user->limit_generate
                    ],
                ], 400);
            }

            // Parameters
            $namaSilabus     = $request->input('name');
            $faseRaw         = $request->input('grade');
            $faseSplit       = explode('|', $faseRaw);
            $faseKelas       = trim($faseSplit[0]);
            $tingkatKelas    = trim($faseSplit[1]);
            $mataPelajaran   = $request->input('subject');
            $addNotes        = $request->input('notes');

            $finalData = CapaianPembelajaran::where('fase', $faseKelas)
            ->where('mata_pelajaran', $mataPelajaran)
            ->select('fase', 'mata_pelajaran', 'element', 'capaian_pembelajaran')
            ->get();

            if (!$finalData) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Data tidak ditemukan untuk kombinasi fase, mata pelajaran, dan elemen capaian yang diberikan',
                    'data' => [],
                ], 400);
            }

            $capaianPembelajaran = $finalData[0]->capaian_pembelajaran;

            $prompt = $this->openAI->generateSyllabusPromptBeta($namaSilabus, $mataPelajaran, $tingkatKelas, $addNotes);

            // Send the message to OpenAI
            $resMessage = $this->openAI->sendMessage($prompt);
            $parsedResponse = json_decode($resMessage, true);

            // Map Fase Kelas
            $faseToKelas = [
                'Fase A' => 'Kelas 1 - 2 SD',
                'Fase B' => 'Kelas 3 - 4 SD',
                'Fase C' => 'Kelas 5 - 6 SD',
                'Fase D' => 'Kelas 7 - 9 SMP',
                'Fase E' => 'Kelas 10 SMA',
                'Fase F' => 'Kelas 11 - 12 SMA'
            ];

            $tingkatKelas = isset($faseToKelas[$faseKelas]) ? "{$faseKelas} ({$faseToKelas[$faseKelas]})" : $faseKelas;

            $user = $request->user();

            $parsedResponse['informasi_umum']['nama_guru'] = $user->name;
            $parsedResponse['informasi_umum']['nama_sekolah'] = $user->school_name;
            $parsedResponse['informasi_umum']['nama_silabus'] = $namaSilabus;

            // Construct the response data for success
            $insertData = SyllabusHistories::create([
                'name' => $namaSilabus,
                'grade' => $tingkatKelas,
                'subject' => $mataPelajaran,
                'notes' => $addNotes,
                'generate_output' => $parsedResponse,
                'user_id' => auth()->id(),
            ]);

            $parsedResponse['id'] = $insertData->id;

            // Decrease user's credit
            $user->decrement('credit', $creditCharge);

            // Credit Logging
            CreditLog::create([
                'user_id' => $user->id,
                'amount' => -$creditCharge,
                'description' => 'Generate Silabus',
            ]);

            // return new APIResponse($responseData);
            return response()->json([
                'status' => 'success',
                'message' => 'Silabus berhasil dibuat!',
                'data' => $parsedResponse,
            ], 200);
        } catch (\Exception $e) {
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
            $templatePath = public_path('word_template/Syllabus_Template.docx');
            $docxTemplate = new DocxTemplate($templatePath);
            $outputPath = public_path('word_output/Syllabus_' . auth()->id() . '-' . md5(time() . '' . rand(1000, 9999)) . '.docx');

            $syllabusHistoryId  = $request->input('id');
            $syllabusHistory   = SyllabusHistories::find($syllabusHistoryId);

            $data = $syllabusHistory->generate_output;
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
            $syllabusHistories = $user->syllabusHistory()
                ->select(['id', 'name', 'grade', 'subject', 'notes', 'user_id', 'created_at', 'updated_at'])
                ->get();

            if ($syllabusHistories->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak ada riwayat silabus untuk akun anda!',
                    'data' => [
                        'generated_num' => 0,
                        'items' => [],
                    ],
                ], 200);
            }

            // Calculate the total generated syllabuses by the user
            $generatedNum = $syllabusHistories->count();

            // Return the response with syllabus histories data
            return response()->json([
                'status' => 'success',
                'message' => 'Riwayat silabus ditampilkan',
                'data' => [
                    'generated_num' => $generatedNum,
                    'items' => $syllabusHistories,
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
            $syllabusHistory = $user->syllabusHistory()->find($id);

            if (!$syllabusHistory) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Riwayat hasil silabus tidak tersedia pada akun ini!',
                    'data' => null,
                ], 404);
            }

            // Return the response with syllabus history data
            return response()->json([
                'status' => 'success',
                'message' => 'Syllabus history retrieved successfully',
                'data' => $syllabusHistory,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage(),
                'data' => json_decode($e->getMessage(), true),
            ], 500);
        }
    }

    public function mtHandler(Request $request)
    {
        return response()->json([
            'status' => 'failed',
            'message' => 'Coming soon!'
        ], 500);
    }
}

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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

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

            $capaianData = CapaianPembelajaran::where('fase', $faseKelas)
                ->where('mata_pelajaran', $mataPelajaran)
                ->get();

            if ($capaianData->isEmpty()) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Data capaian pembelajaran tidak ditemukan untuk fase dan mata pelajaran yang dipilih.',
                ], 404);
            }

            $prompt = $this->openAI->generateSyllabusPromptBeta($namaSilabus, $mataPelajaran, $tingkatKelas, $addNotes);
            $resMessage = $this->openAI->sendMessage($prompt);
            $parsedResponse = json_decode($resMessage, true);

            if ($parsedResponse === null) {
                throw new \Exception('Gagal memproses respons dari AI. Format JSON tidak valid.');
            }

            $rules = [
                'informasi_umum' => 'required|array',
                'silabus_pembelajaran' => 'required|array',
                'informasi_umum.mata_pelajaran' => 'required|string',
                'silabus_pembelajaran.inti_silabus' => 'required|array',
                'silabus_pembelajaran.inti_silabus.*.kompetensi_dasar' => 'required|array',
                'silabus_pembelajaran.inti_silabus.*.materi_pembelajaran' => 'required|array',
                'silabus_pembelajaran.inti_silabus.*.kegiatan_pembelajaran' => 'required|array',
            ];

            $validator = Validator::make($parsedResponse, $rules);

            if ($validator->fails()) {
                \Log::error('AI JSON Structure Failed: ' . $validator->errors()->first());
                throw new \Exception('Terjadi kesalahan dalam membuat silabus. Silakan coba lagi.');
            }

            // Map Fase Kelas
            $faseToKelas = [
                'Fase A' => 'Kelas 1 - 2 SD',
                'Fase B' => 'Kelas 3 - 4 SD',
                'Fase C' => 'Kelas 5 - 6 SD',
                'Fase D' => 'Kelas 7 - 9 SMP',
                'Fase E' => 'Kelas 10 SMA',
                'Fase F' => 'Kelas 11 - 12 SMA'
            ];

            $tingkatKelasMapped = $faseToKelas[$faseKelas] ?? $faseKelas;

            $parsedResponse['informasi_umum']['nama_guru'] = $user->name;
            $parsedResponse['informasi_umum']['nama_sekolah'] = $user->school_name;
            $parsedResponse['informasi_umum']['nama_silabus'] = $namaSilabus;

            $insertData = DB::transaction(function () use ($user, $creditCharge, $namaSilabus, $tingkatKelasMapped, $mataPelajaran, $addNotes, $parsedResponse) {
                $history = SyllabusHistories::create([
                    'name' => $namaSilabus,
                    'grade' => $tingkatKelasMapped,
                    'subject' => $mataPelajaran,
                    'notes' => $addNotes,
                    'generate_output' => $parsedResponse,
                    'user_id' => $user->id,
                ]);

                // Kurangi kredit user
                $user->decrement('credit', $creditCharge);

                // Catat log kredit
                CreditLog::create([
                    'user_id' => $user->id,
                    'amount' => -$creditCharge,
                    'description' => 'Generate Silabus: ' . $namaSilabus,
                ]);

                return $history;
            });

            $parsedResponse['id'] = $insertData->id;

            return response()->json([
                'status' => 'success',
                'message' => 'Silabus berhasil dibuat!',
                'data' => $parsedResponse,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage(),
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

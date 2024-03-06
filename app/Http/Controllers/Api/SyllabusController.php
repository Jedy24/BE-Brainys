<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\OpenAIService;

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
                'subject' => 'required',
                'grade' => 'required',
                'nip' => 'required',
                'notes' => 'required',
            ]);

            // Retrieve the authenticated user
            $user = $request->user();

            // Parameters
            $mataPelajaran   = $request->input('subject');
            $tingkatKelas    = $request->input('grade');
            $NIP             = $request->input('nip');
            $addNotes        = $request->input('notes');
            $prompt          = $this->openAI->generateSyllabusPromptBeta($mataPelajaran, $tingkatKelas, $NIP, $addNotes);

            // Send the message to OpenAI
            $resMessage = $this->openAI->sendMessage($prompt);

            $parsedResponse = json_decode($resMessage, true);
            $user = $request->user();

            $parsedResponse['informasi_umum']['nama_guru'] = $user->name;
            $parsedResponse['informasi_umum']['nama_sekolah'] = $user->school_name;

            // Construct the response data for success
            $insertData = SyllabusHistories::create([
                'subject' => $mataPelajaran,
                'grade' => $tingkatKelas,
                'nip' => $NIP,
                'notes' => $addNotes,
                'generate_output' => $parsedResponse,
                'user_id' => auth()->id(),
            ]);

            $parsedResponse['id'] = $insertData->id;

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
                ->select(['id', 'subject', 'grade', 'nip', 'notes', 'user_id', 'created_at', 'updated_at'])
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

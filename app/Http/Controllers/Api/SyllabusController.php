<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\OpenAIService;

use App\Models\MaterialHistories;
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
                'subject' => 'required',
                'grade' => 'required',
                'notes' => 'required',
            ]);

            // Parameters
            $syllabusName   = $request->input('name');
            $mataPelajaran  = $request->input('subject');
            $tingkatKelas   = $request->input('grade');
            $addNotes       = $request->input('notes');
            $prompt         = $this->openAI->generateSyllabusPrompt($mataPelajaran, $tingkatKelas, $addNotes);

            // Send the message to OpenAI
            $resMessage = $this->openAI->sendMessage($prompt);

            $parsedResponse = json_decode($resMessage, true);

            // Construct the response data for success
            MaterialHistories::create([
                'name' => $syllabusName,
                'subject' => $mataPelajaran,
                'grade' => $tingkatKelas,
                'notes' => $addNotes,
                'output_data' => $parsedResponse,
                'user_id' => auth()->id(),
            ]);

            // return new APIResponse($responseData);
            return response()->json([
                'status' => 'success',
                'message' => 'Silabus berhasil dihasilkan',
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

            $data = $request->input('data');
            // $customDateTime = new DateTime('2024-01-05 21:20:00');
            // $formattedDateTime = $customDateTime->format('d F Y, H:i');
            // $data['format_date_time'] = $formattedDateTime;

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
                ->select(['id', 'name', 'subject', 'grade', 'notes', 'created_at', 'updated_at', 'user_id'])
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
                    'message' => 'Syllabus history not found',
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
            'status' => 'error',
            'message' => 'Coming soon!'
        ], 500);
    }
}

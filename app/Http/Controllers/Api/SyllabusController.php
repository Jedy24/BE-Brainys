<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\OpenAIService;

use App\Models\SyllabusHistory;
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
                'pelajaran' => 'required',
                'kelas' => 'required',
                'notes' => 'required',
            ]);

            // Parameters
            $mataPelajaran  = $request->input('pelajaran');
            $tingkatKelas   = $request->input('kelas');
            $addNotes       = $request->input('notes');
            $prompt         = $this->openAI->generateSyllabusPrompt($mataPelajaran, $tingkatKelas, $addNotes);

            // Send the message to OpenAI
            $resMessage = $this->openAI->sendMessage($prompt);

            // Parse the response from OpenAI if needed
            $parsedResponse = json_decode($resMessage, true);
            // $parsedResponse = $resMessage;

            // Construct the response data for success
            $syllabusHistory = SyllabusHistory::create([
                'subject' => $mataPelajaran,
                'class' => $tingkatKelas,
                'notes' => $addNotes,
                'output_data' => $parsedResponse,
                'user_id' => auth()->id(),
            ]);

            // return new APIResponse($responseData);
            return response()->json([
                'status' => 'success',
                'message' => 'Request processed successfully',
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
            $outputPath = public_path('word_output/Syllabus_' . md5(time() . '' . rand(1000, 9999)) . '.docx');

            $data = $request->input('data');

            $docxTemplate->merge($data, $outputPath, false, false);

            // Assuming the merge operation is successful
            return response()->json([
                'status' => 'success',
                'message' => 'Word document generated successfully',
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
                ->select(['id', 'subject', 'class', 'notes', 'created_at', 'updated_at', 'user_id'])
                ->get();

            if ($syllabusHistories->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No syllabus histories found for the user',
                    'data' => [],
                ], 200);
            }

            // Return the response with syllabus histories data
            return response()->json([
                'status' => 'success',
                'message' => 'Syllabus histories retrieved successfully',
                'data' => $syllabusHistories,
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
}

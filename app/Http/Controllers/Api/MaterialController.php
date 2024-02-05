<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\OpenAIService;

use App\Models\SyllabusHistory;
use icircle\Template\Docx\DocxTemplate;

class MaterialController extends Controller
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
            $prompt         = $this->openAI->generateMaterialsPrompt($mataPelajaran, $tingkatKelas, $addNotes);

            // Send the message to OpenAI
            $resMessage = $this->openAI->sendMessage($prompt);

            $parsedResponse = json_decode($resMessage, true);

            // Construct the response data for success
            SyllabusHistory::create([
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
                'message' => 'Bahan Ajar berhasil dihasilkan',
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
}

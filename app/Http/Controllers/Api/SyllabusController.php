<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\OpenAIService;

use Illuminate\Http\JsonResponse;
use App\Http\Resources\APIResponse;

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

            // Assuming $parsedResponse is an array with the OpenAI response

            // Construct the response data for success
            $responseData = [
                'success' => true,
                'message' => 'Request processed successfully',
                'http_code' => JsonResponse::HTTP_OK,
                'data' => $parsedResponse,
            ];

            return new APIResponse($responseData);
        } catch (\Exception $e) {
            $errorResponse = [
                'success' => false,
                'message' => $e->getMessage(),
                'http_code' => JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
                'data' => json_decode($e->getMessage(), true),
            ];
            return new APIResponse($errorResponse);
        }
    }

    public function convertToWord(Request $request)
    {
        try {
            $templatePath = public_path('word_template/Syllabus_Template.docx');

            $docxTemplate = new DocxTemplate($templatePath);
            $outputPath = public_path('word_output/Syllabus_' . md5(time() . '' . rand(1000, 9999)) . '.docx');

            $data = $request->input('data');

            $docxTemplate->merge($data, $outputPath, false, true);

            // Assuming the merge operation is successful
            $responseData = [
                'success' => true,
                'message' => 'Word document generated successfully',
                'http_code' => JsonResponse::HTTP_OK,
                'data' => ['output_path' => $outputPath, 'download_url' => url('word_output/' . basename($outputPath))],
            ];

            return new APIResponse($responseData);
        } catch (\Exception $e) {
            $errorResponse = [
                'success' => false,
                'message' => $e->getMessage(),
                'http_code' => JsonResponse::HTTP_INTERNAL_SERVER_ERROR,
                'data' => json_decode($e->getMessage(), true),
            ];

            return new APIResponse($errorResponse);
        }
    }
}

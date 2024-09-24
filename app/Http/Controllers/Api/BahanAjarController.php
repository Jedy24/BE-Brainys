<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BahanAjarHistories;
use App\Models\CreditLog;
use App\Models\ModuleCreditCharge;
use App\Services\OpenAIService;
use App\Services\PPTBahanAjar;
use icircle\Template\Docx\DocxTemplate;
use Illuminate\Http\Request;

class BahanAjarController extends Controller
{
    private $openAI;
    private $pptBahanAjar;

    public function __construct(OpenAIService $openAI, PPTBahanAjar $pptBahanAjar)
    {
        $this->openAI = $openAI;
        $this->pptBahanAjar = $pptBahanAjar;
    }

    public function generate(Request $request)
    {
        try {
            // Input validation
            $request->validate([
                'name'  =>'required',
                'subject' => 'required',
                'grade' => 'required',
                'notes' => 'required',
            ]);

            // Retrieve the authenticated user
            $user = $request->user();

            // Check if the user is active
            if ($user->is_active === 0) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'User tidak aktif. Anda tidak dapat membuat bahan ajar.',
                ], 400);
            }

            // Check if the user has less than 20 limit generate
            $moduleCredit = ModuleCreditCharge::where('slug', 'bahan-ajar')->first();
            $creditCharge = $moduleCredit->credit_charged_generate ?? 1;
            if ($user->credit < $creditCharge) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Anda telah mencapai batas maksimal untuk riwayat bahan ajar.',
                    'data' => [
                        'user_credit' => $user->credit,
                        'generated_all_num' => $user->generateAllSum(),
                        'limit_all_num' => $user->limit_generate
                    ],
                ], 400);
            }

            // Parameters
            $namaBahanAjar  = $request->input('name');
            $mataPelajaran  = $request->input('subject');
            $tingkatKelas   = $request->input('grade');
            $addNotes       = $request->input('notes');
            $prompt         = $this->openAI->generateBahanAjarPrompt($mataPelajaran, $tingkatKelas, $addNotes);

            // Send the message to OpenAI
            $resMessage = $this->openAI->sendMessage($prompt);

            $parsedResponse = json_decode($resMessage, true);
            $user = $request->user();

            $parsedResponse['informasi_umum']['nama_bahan_ajar'] = $namaBahanAjar;
            $parsedResponse['informasi_umum']['penyusun']        = $user->name;
            $parsedResponse['informasi_umum']['instansi']        = $user->school_name;

            // Construct the response data for success
            $insertData = BahanAjarHistories::create([
                'name' => $namaBahanAjar,
                'subject' => $mataPelajaran,
                'grade' => $tingkatKelas,
                'notes' => $addNotes,
                'generate_output' => $parsedResponse,
                'user_id' => auth()->id(),
            ]);
            
            // Decrease user's credit
            $user->decrement('credit', $creditCharge);

            // Credit Logging
            CreditLog::create([
                'user_id' => $user->id,
                'amount' => -$creditCharge,
                'description' => 'Generate Bahan Ajar',
            ]);

            $parsedResponse['id'] = $insertData->id;

            // return new APIResponse($responseData);
            return response()->json([
                'status' => 'success',
                'message' => 'Bahan ajar berhasil dihasilkan!',
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
            $templatePath   = public_path('word_template/Bahan_Ajar_Template.docx');
            $docxTemplate   = new DocxTemplate($templatePath);
            $outputPath     = public_path('word_output/Bahan_Ajar_' . auth()->id() . '_' . md5(time() . '' . rand(1000, 9999)) . '.docx');

            $bahanAjarHistoryId  = $request->input('id');
            $bahanAjarHistory    = BahanAjarHistories::find($bahanAjarHistoryId);

            $data = $bahanAjarHistory->generate_output;
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

    public function convertToPPT(Request $request)
    {
        try {
            $outputPath     = public_path('ppt_output/Bahan_Ajar_' . auth()->id() . '_' . md5(time() . '' . rand(1000, 9999)) . '.pptx');

            $BahanAjarId    = $request->input('id');
            $BahanAjarData  = BahanAjarHistories::find($BahanAjarId);
            $BahanAjarOut   = $BahanAjarData->generate_output;
            $BahanAjarOut   = json_decode(json_encode($BahanAjarData->generate_output), true);

            // dd($BahanAjarOut);

            $this->pptBahanAjar->createPresentation($BahanAjarOut, $outputPath);

            // Assuming the merge operation is successful
            return response()->json([
                'status' => 'success',
                'message' => 'Dokumen PPT berhasil dibuat',
                'data' => ['output_path' => $outputPath, 'download_url' => url('ppt_output/' . basename($outputPath))],
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

            // Get bahan ajar histories for the authenticated user
            $bahanAjarHistories = $user->bahanAjarHistory()
                ->select(['id', 'name', 'subject', 'grade', 'notes', 'user_id', 'created_at', 'updated_at'])
                ->get();

            if ($bahanAjarHistories->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak ada riwayat bahan ajar untuk akun anda!',
                    'data' => [
                        'generated_num' => 0,
                        'items' => [],
                    ],
                ], 200);
            }

            // Calculate the total generated bahan ajar by the user
            $generatedNum = $bahanAjarHistories->count();

            // Return the response with bahan ajar histories data
            return response()->json([
                'status' => 'success',
                'message' => 'Riwayat bahan ajar ditampilkan',
                'data' => [
                    'generated_num' => $generatedNum,
                    'items' => $bahanAjarHistories,
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

            // Get a specific bahan ajar history by ID for the authenticated user
            $bahanAjarHistory = $user->bahanAjarHistory()->find($id);

            if (!$bahanAjarHistory) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Riwayat hasil bahan ajar tidak tersedia pada akun ini!',
                    'data' => null,
                ], 404);
            }

            // Return the response with bahan ajar history data
            return response()->json([
                'status' => 'success',
                'message' => 'Bahan ajar history retrieved successfully',
                'data' => $bahanAjarHistory,
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

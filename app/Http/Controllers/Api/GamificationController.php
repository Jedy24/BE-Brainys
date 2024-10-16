<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CreditLog;
use App\Models\GamificationHistories;
use App\Models\ModuleCreditCharge;
use App\Services\OpenAIService;
use App\Services\PPTGamification;
use icircle\Template\Docx\DocxTemplate;
use Illuminate\Http\Request;

class GamificationController extends Controller
{
    private $openAI;
    protected $pptGamification;

    public function __construct(OpenAIService $openAI, PPTGamification $pptGamification)
    {
        $this->openAI = $openAI;
        $this->pptGamification = $pptGamification;
    }

    public function generate(Request $request)
    {
        try {
            // Input validation
            $request->validate([
                'name' => 'required',
                'game_scheme' => 'required',
                'grade' => 'required',
                'subject' => 'required',
                'material' => 'required',
                'notes' => 'nullable',
            ]);

            // Retrieve the authenticated user
            $user = $request->user();

            // Check if the user is active
            if ($user->is_active === 0) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'User tidak aktif. Anda tidak dapat membuat gamifikasi.',
                ], 400);
            }

            // Check if the user has less than 20 material histories
            $moduleCredit = ModuleCreditCharge::where('slug', 'gamifikasi')->first();
            $creditCharge = $moduleCredit->credit_charged_generate ?? 1;
            if ($user->credit < $creditCharge) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Anda telah mencapai batas maksimal untuk riwayat gamifikasi.',
                    'data' => [
                        'user_credit' => $user->credit,
                        'generated_all_num' => $user->generateAllSum(),
                        'limit_all_num' => $user->limit_generate
                    ],
                ], 400);
            }

            // Parameters
            $gameName       = $request->input('name');
            $skema          = $request->input('game_scheme');
            $tingkatKelas   = $request->input('grade');
            $mataPelajaran  = $request->input('subject');
            $material       = $request->input('material');
            $addNotes       = $request->input('notes');
            $prompt         = $this->prompt($mataPelajaran, $tingkatKelas, $skema, $material, $addNotes);

            // Send the message to OpenAI
            $resMessage = $this->openAI->sendMessage($prompt);
            $parsedResponse = json_decode($resMessage, true);

            $user = $request->user();

            $parsedResponse['informasi_umum']['penyusun'] = $user->name;
            $parsedResponse['informasi_umum']['instansi'] = $user->school_name;
            $parsedResponse['informasi_umum']['kelas'] = $tingkatKelas;
            $parsedResponse['informasi_umum']['mata_pelajaran'] = $mataPelajaran;
            $parsedResponse['informasi_umum']['materi_pelajaran'] = $material;
            $parsedResponse['informasi_umum']['nama_gamifikasi'] = $gameName;
            $parsedResponse['informasi_umum']['tahun_penyusunan'] = Date('Y');

            // Construct the response data for success
            $insertData = GamificationHistories::create([
                'name' => $gameName,
                'subject' => $mataPelajaran,
                'grade' => $tingkatKelas,
                'notes' => $addNotes,
                'game_scheme' => $request->input('game_scheme'),
                'output_data' => $parsedResponse,
                'user_id' => auth()->id(),
            ]);
            
            // Decrease user's credit
            $user->decrement('credit', $creditCharge);

            // Credit Logging
            CreditLog::create([
                'user_id' => $user->id,
                'amount' => -$creditCharge,
                'description' => 'Generate Gamifikasi',
            ]);

            $parsedResponse['id'] = $insertData->id;

            // return new APIResponse($responseData);
            return response()->json([
                'status' => 'success',
                'message' => 'Gamifikasi berhasil dihasilkan',
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
            $templatePath   = public_path('word_template/Gamifikasi_Template.docx');
            $docxTemplate   = new DocxTemplate($templatePath);
            $outputPath     = public_path('word_output/Gamifikasi_' . auth()->id() . '_' . md5(time() . '' . rand(1000, 9999)) . '.docx');

            $gamificationId     = $request->input('id');
            $gamificationData   = GamificationHistories::find($gamificationId);

            $data = $gamificationData->output_data;
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
            $outputPath     = public_path('ppt_output/Gamifikasi_' . auth()->id() . '_' . md5(time() . '' . rand(1000, 9999)) . '.pptx');

            $gamificationId     = $request->input('id');
            $gamificationData   = GamificationHistories::find($gamificationId);
            $gamificationOut    = $gamificationData->output_data;
            $gamificationOut    = json_decode(json_encode($gamificationData->output_data), true);

            $this->pptGamification->createPresentation($gamificationOut, $outputPath);

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

            // Get gamification histories for the authenticated user
            $gamificationHistories = $user->gamificationHistory()
                ->select(['id', 'name', 'subject', 'grade', 'notes', 'game_scheme', 'output_data', 'created_at', 'updated_at', 'user_id'])
                ->get();

            if ($gamificationHistories->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak ada riwayat gamifikasi untuk akun anda!',
                    'data' => [
                        'generated_num' => 0,
                        'items' => [],
                    ],
                ], 200);
            }

            // Calculate the total generated gamification histories by the user
            $generatedNum = $gamificationHistories->count();

            // Return the response with gamification histories data
            return response()->json([
                'status' => 'success',
                'message' => 'Riwayat gamifikasi ditampilkan',
                'data' => [
                    'generated_num' => $generatedNum,
                    'items' => $gamificationHistories,
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

            // Get a specific gamification history by ID for the authenticated user
            $gamificationHistory = $user->gamificationHistory()->find($id);

            if (!$gamificationHistory) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Riwayat hasil gamifikasi tidak tersedia di akun ini!',
                    'data' => null,
                ], 404);
            }

            // Return the response with gamification history data
            return response()->json([
                'status' => 'success',
                'message' => 'Riwayat gamifikasi ditampilkan',
                'data' => $gamificationHistory,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage(),
                'data' => json_decode($e->getMessage(), true),
            ], 500);
        }
    }

    public function prompt($subject, $grade, $game_scheme, $material, $notes)
    {
        $prompt = '';
        $prompt .= '
        You are an expert education doctor tasked with designing a gamified learning format. Use the following parameters:
    
        Subject: ' . $subject . '
        Grade: ' . $grade . '
        Game Scheme: ' . $game_scheme . '
        Material: ' . $material . '
        Notes: ' . $notes . '
    
        Create a JSON format for gamified learning in Indonesian, including:
    
        tema: A theme related to the subject.
        konsep_utama: The main concept focusing on practical activities and competition.
        skema_game: ' . $game_scheme . '
        elemen_gamifikasi: Gamification elements consisting of titles and descriptions.
        misi_dan_tantangan: Missions and challenges with types, descriptions, and points.
        langkah_implementasi: Create the `langkah_implementasi` section which will be displayed as instructions for students, including:
            step: Numbered steps for implementation.
            title: Brief titles for each step.
            description: Detailed instructions for each step presented as an array. Minimum 2 points.
        
        Game Scheme Explanation:
        - For individual game scheme: Each student competes individually, earning points and achievements based on their own efforts.
        - For group game scheme: Students collaborate in teams to complete missions and challenges, fostering teamwork and collective achievement.
    
        Example format:
        {
            "tema": "",
            "konsep_utama": "",
            "skema_game": "",
            "elemen_gamifikasi": [
                {
                    "judul": "",
                    "deskripsi": ""
                },
                {
                    "judul": "",
                    "deskripsi": ""
                },
                {
                    "judul": "",
                    "deskripsi": ""
                },
                {
                    "judul": "",
                    "deskripsi": ""
                }
            ],
            "misi_dan_tantangan": [
                {
                    "jenis": "",
                    "deskripsi": "",
                    "poin": 0
                },
                {
                    "jenis": "",
                    "deskripsi": "",
                    "poin": 0
                },
                {
                    "jenis": "",
                    "deskripsi": "",
                    "poin": 0
                },
                {
                    "jenis": "",
                    "deskripsi": "",
                    "poin": 0
                }
            ],
            "langkah_implementasi": [
                {
                    "langkah": 1,
                    "judul": "",
                    "deskripsi": []
                },
                {
                    "langkah": 2,
                    "judul": "",
                    "deskripsi": []
                },
                {
                    "langkah": 3,
                    "judul": "",
                    "deskripsi": []
                },
                {
                    "langkah": 4,
                    "judul": "",
                    "deskripsi": []
                },
                {
                    "langkah": 5,
                    "judul": "",
                    "deskripsi": []
                },
                {
                    "langkah": 6,
                    "judul": "",
                    "deskripsi": []
                },
                {
                    "langkah": 7,
                    "judul": "",
                    "deskripsi": []
                },
                {
                    "langkah": 8,
                    "judul": "",
                    "deskripsi": []
                }
            ]
        }
    
        Subject: This refers to the specific subject or topic that the gamified learning format will be based on.
        Grade: This indicates the educational level or grade of the students for whom the format is being designed.
        Game Scheme: This describes whether the gamification will be conducted individually or in groups. Individual schemes focus on personal achievement, while group schemes emphasize collaboration and teamwork.
        Material: The educational content or material that will be covered in the gamified learning activities.
        Notes: Any additional notes or special instructions that might be relevant to the design of the gamified learning format.
        
        ';
        $prompt .= '';

        return $prompt;
    }
}

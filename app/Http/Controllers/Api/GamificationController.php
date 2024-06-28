<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\GamificationHistories;
use App\Services\OpenAIService;
use icircle\Template\Docx\DocxTemplate;
use Illuminate\Http\Request;

class GamificationController extends Controller
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
                'game_scheme' => 'required',
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

            // Parameters
            $gameName       = $request->input('name');
            $mataPelajaran  = $request->input('subject');
            $tingkatKelas   = $request->input('grade');
            $skema          = $request->input('game_scheme');
            $addNotes       = $request->input('notes');
            $prompt         = $this->prompt($mataPelajaran, $tingkatKelas, $skema, $addNotes);

            // Send the message to OpenAI
            $resMessage = $this->openAI->sendMessage($prompt);
            $parsedResponse = json_decode($resMessage, true);

            $user = $request->user();

            $parsedResponse['informasi_umum']['nama_gamifikasi'] = $gameName;
            $parsedResponse['informasi_umum']['penyusun'] = $user->name;
            $parsedResponse['informasi_umum']['instansi'] = $user->school_name;
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

    public function prompt($subject, $grade, $game_scheme, $notes)
    {
        $prompt = '';
        $prompt .= '
        You are an expert education doctor tasked with designing a gamified learning format. Use the following parameters:
    
        Subject: ' . $subject . '
        Grade: ' . $grade . '
        Game Scheme: ' . $game_scheme . '
        Notes: ' . $notes . '
    
        Create a JSON format for gamified learning in Indonesian, including:
    
        tema: A theme related to the subject.
        konsep_utama: The main concept focusing on practical activities and competition.
        skema_game: '.$game_scheme.'
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
    
        ';
        $prompt .= '';

        return $prompt;
    }
}

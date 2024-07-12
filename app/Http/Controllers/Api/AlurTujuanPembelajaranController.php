<?php

namespace App\Http\Controllers\Api;

use App\Exports\ATPExport;
use App\Http\Controllers\Controller;
use App\Models\AlurTujuanPembelajaranHistories;
use App\Models\CapaianPembelajaran;
use App\Services\OpenAIService;
use icircle\Template\Docx\DocxTemplate;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class AlurTujuanPembelajaranController extends Controller
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
                'phase' => 'required',
                'subject' => 'required',
                'element' => 'required',
                'weeks' => 'required',
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
            $name               = $request->input('name');
            $fase               = $request->input('phase');
            $mataPelajaran      = $request->input('subject');
            $elemen             = $request->input('element');
            $pekan              = $request->input('weeks');
            $deskripsiNotes     = $request->input('notes');

            // Capaian Pembelajaran
            $finalData = CapaianPembelajaran::where('fase', $fase)
                ->where('mata_pelajaran', $mataPelajaran)
                ->where('element', $elemen)
                ->select('fase', 'mata_pelajaran', 'element', 'capaian_pembelajaran', 'capaian_pembelajaran_redaksi')
                ->get();

            if ($finalData->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'No data CP found',
                    'data' => [],
                ]);
            }

            $capaianPembelajaran = $finalData->pluck('capaian_pembelajaran')->implode(' ');
            $capaianPembelajaranTahun = '';

            $prompt = $this->prompt($fase, $mataPelajaran, $elemen, $capaianPembelajaran, $capaianPembelajaranTahun, $pekan, $deskripsiNotes);

            // Send the message to OpenAI
            $resMessage = $this->openAI->sendMessage($prompt);
            $parsedResponse = json_decode($resMessage, true);

            $user = $request->user();

            $parsedResponse['informasi_umum']['nama']               = $name;
            $parsedResponse['informasi_umum']['penyusun']           = $user->name;
            $parsedResponse['informasi_umum']['instansi']           = $user->school_name;
            $parsedResponse['informasi_umum']['kelas']              = $fase;
            $parsedResponse['informasi_umum']['mata_pelajaran']     = $mataPelajaran;
            $parsedResponse['informasi_umum']['elemen']             = $elemen;
            $parsedResponse['informasi_umum']['cp']                 = $capaianPembelajaran;
            $parsedResponse['informasi_umum']['cp_tahun']           = $capaianPembelajaranTahun;
            $parsedResponse['informasi_umum']['pekan']              = $pekan;
            $parsedResponse['informasi_umum']['tahun_penyusunan']   = Date('Y');

            // Save to AlurTujuanPembelajaranHistories
            $history = AlurTujuanPembelajaranHistories::create([
                'user_id' => $user->id,
                'name' => $name,
                'phase' => $fase,
                'subject' => $mataPelajaran,
                'element' => $elemen,
                'weeks' => $pekan,
                'notes' => $deskripsiNotes,
                'output_data' => $parsedResponse,
            ]);

            $parsedResponse['id'] = $history->id;

            // return new APIResponse($responseData);
            return response()->json([
                'status' => 'success',
                'message' => 'ATP berhasil dihasilkan',
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
            $templatePath   = public_path('word_template/Alur_Tujuan_Pembelajaran_Template.docx');
            $docxTemplate   = new DocxTemplate($templatePath);
            $outputPath     = public_path('word_output/Alur_Tujuan_Pembelajaran_' . auth()->id() . '_' . md5(time() . '' . rand(1000, 9999)) . '.docx');

            $AlurTujuanId   = $request->input('id');
            $AlurTujuan     = AlurTujuanPembelajaranHistories::find($AlurTujuanId);

            $data = $AlurTujuan->output_data;

            for ($i = 0; $i < count($data['alur']); $i++) {
                $data['alur'][$i]['kata_frase_kunci'] = implode(", ", $data['alur'][$i]['kata_frase_kunci']);
                $data['alur'][$i]['profil_pelajar_pancasila'] = implode(", ", $data['alur'][$i]['profil_pelajar_pancasila']);
            }

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

    public function convertToExcel(Request $request)
    {
        try {
            $AlurTujuanId = $request->input('id');
            $AlurTujuan = AlurTujuanPembelajaranHistories::find($AlurTujuanId);
    
            $data = $AlurTujuan->output_data;
            // $dataArray = json_decode($data, true);
    
            $fileName = 'capaian_pembelajaran.xlsx';
            $filePath = storage_path('app/public/' . $fileName);
    
            // Buat dan simpan file Excel
            Excel::store(new ATPExport($data), 'public/' . $fileName);
    
            $outputPath = asset('storage/' . $fileName);
            $downloadUrl = url('storage/' . $fileName);
    
            return response()->json([
                'status' => 'success',
                'message' => 'Dokumen Excel berhasil dibuat',
                'data' => [
                    'output_path' => $outputPath,
                    'download_url' => $downloadUrl
                ]
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

            // Get learning objectives histories for the authenticated user
            $alurHistories = $user->alurTujuanPembelajaranHistory()
                ->select(['id', 'name', 'phase', 'subject', 'element', 'weeks', 'notes', 'output_data', 'created_at', 'updated_at'])
                ->get();

            if ($alurHistories->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak ada riwayat alur tujuan pembelajaran untuk akun anda!',
                    'data' => [
                        'generated_num' => 0,
                        'items' => [],
                    ],
                ], 200);
            }

            // Calculate the total generated learning objectives by the user
            $generatedNum = $alurHistories->count();

            // Return the response with learning objectives histories data
            return response()->json([
                'status' => 'success',
                'message' => 'Riwayat alur tujuan pembelajaran ditampilkan',
                'data' => [
                    'generated_num' => $generatedNum,
                    'items' => $alurHistories,
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

            // Get a specific learning objectives history by ID for the authenticated user
            $alurHistory = $user->alurTujuanPembelajaranHistory()->find($id);

            if (!$alurHistory) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Riwayat alur tujuan pembelajaran tidak tersedia di akun ini!',
                    'data' => null,
                ], 404);
            }

            // Return the response with learning objectives history data
            return response()->json([
                'status' => 'success',
                'message' => 'Riwayat alur tujuan pembelajaran berhasil diambil',
                'data' => $alurHistory,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage(),
                'data' => json_decode($e->getMessage(), true),
            ], 500);
        }
    }

    public function prompt($fase, $mataPelajaran, $elemen, $capaianPembelajaran, $capaianPembelajaranTahun, $pekan, $deskripsiNotes)
    {
        $prompt = '';
        $prompt .= '
        Generate a JSON object for a learning objectives flow based on the following parameters:

        - **fase**: ' . $fase . ' 
        - **mata_pelajaran**: ' . $mataPelajaran . ' 
        - **elemen**: ' . $elemen . ' 
        - **capaian_pembelajaran**: ' . $capaianPembelajaran . ' 
        - **capaian_pembelajaran_per_tahun**: ' . $capaianPembelajaranTahun . ' 
        - **pekan**: ' . $pekan . ' 

        Create an array called "alur" where the number of learning objectives matches the specified number of weeks (`pekan`).
        Each learning objective should be based on the `capaian_pembelajaran` and `elemen`.
        Additional Notes in Indonesian: ' . $deskripsiNotes . '
        
        **Tujuan Pembelajaran (TP)** represents the description of achieving three aspects of competence (knowledge, skills, attitudes) that need to be developed through one or more learning activities. 
        The learning objectives are arranged chronologically based on the sequence of learning over time that serves as a prerequisite towards achieving the learning outcomes (Capaian Pembelajaran - CP).

        Operationally, the components of Learning Objectives should include:
        
        - **Kompetensi**: The capability that can be demonstrated by students or shown in the form of a product indicating that students have successfully achieved the learning objective.
        - **Konten**: The core knowledge or main concepts that need to be understood by the end of a learning unit.
        - **Variasi**: Describes creative, critical, and higher-order thinking skills that students need to master to achieve the learning objectives, such as evaluating, analyzing, predicting, creating, etc.
        
        Include the following fields for each objective:
        - **no**: A sequential number for the learning objective.
        - **tujuan_pembelajaran**: Learning Objective that specifies what students should achieve each week. *Tujuan Pembelajaran (TP)** represents the description of achieving three aspects of competence (knowledge, skills, attitudes) that need to be developed through one or more learning activities. The learning objectives are arranged chronologically based on the sequence of learning over time that serves as a prerequisite towards achieving the learning outcomes (Capaian Pembelajaran - CP).
        - **kata_frase_kunci**: Key Words/Phrases related to the learning objective.
        - **profil_pelajar_pancasila**: Pancasila Student Profile characteristics that the objective aims to develop.
        - **glorasium**: Glossary or explanation related to the learning objective.

        The output should be in Indonesian and in JSON format. Each entry in the "alur" array should correspond to one week, ensuring there are as many entries as the specified number of weeks (`pekan`).

        Here is the structure of the JSON object:
        
        {
            "fase": "{Fase}",
            "mata_pelajaran": "{Mata Pelajaran}",
            "elemen": "{Elemen}",
            "capaian_pembelajaran": "{Capaian Pembelajaran}",
            "capaian_pembelajaran_per_tahun": "{Capaian Pembelajaran per Tahun}",
            "pekan": "{Pekan}",
            "alur": [
                {
                    "no": 1,
                    "tujuan_pembelajaran": "{Tujuan Pembelajaran Pekan 1, 30 sampai 50 kata paragraf narasi}",
                    "kata_frase_kunci": [
                        "{Kata/Frase Kunci Pekan 1}"
                    ],
                    "profil_pelajar_pancasila": [
                        "{Profil Pelajar Pancasila Pekan 1}"
                    ],
                    "glorasium": "{Glorasium Pekan 1}"
                },
                {
                    "no": 2,
                    "tujuan_pembelajaran": "{Tujuan Pembelajaran Pekan 2, 30 sampai 50 kata paragraf narasi}",
                    "kata_frase_kunci": [
                        "{Kata/Frase Kunci Pekan 2}"
                    ],
                    "profil_pelajar_pancasila": [
                        "{Profil Pelajar Pancasila Pekan 2}"
                    ],
                    "glorasium": "{Glorasium Pekan 2}"
                }
                // Continue this pattern up to the number of weeks specified
            ]
        }

        ';
        $prompt .= '';

        return $prompt;
    }
}

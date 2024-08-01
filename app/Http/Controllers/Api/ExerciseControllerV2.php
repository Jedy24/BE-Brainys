<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\CapaianPembelajaran;
use App\Models\ExerciseV2Histories;
use App\Services\OpenAIService;
use icircle\Template\Docx\DocxTemplate;
use Illuminate\Http\Request;

class ExerciseControllerV2 extends Controller
{
    private $openAI;

    public function __construct(OpenAIService $openAI)
    {
        $this->openAI = $openAI;
    }

    public function generateEssay(Request $request)
    {
        try {
            // Validasi input
            $request->validate([
                'name' => 'required|string',
                'phase' => 'required|string',
                'subject' => 'required|string',
                'element' => 'required|string',
                'number_of_questions' => 'required|integer|min:1|max:15',
                'notes' => 'nullable|string',
            ]);

            // Retrieve the authenticated user
            $user = $request->user();

            // Check if the user is active
            if ($user->is_active === 0) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'User tidak aktif. Anda tidak dapat membuat latihan soal.',
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

            // Ambil data dari permintaan
            $exerciseName   = $request->input('name');
            // $faseKelas      = $request->input('phase');
            $faseRaw        = $request->input('phase');
            $faseSplit      = explode('|', $faseRaw);
            $faseKelas      = trim($faseSplit[0]);
            $kelas          = trim($faseSplit[1]);
            $mataPelajaran  = $request->input('subject');
            $element        = $request->input('element');
            $jumlahSoal     = $request->input('number_of_questions');
            $notes          = $request->input('notes') ? $request->input('notes') : '';

            // Capaian Pembelajaran
            $finalData = CapaianPembelajaran::where('fase', $faseKelas)
                ->where('mata_pelajaran', $mataPelajaran)
                ->where('element', $element)
                ->select('fase', 'mata_pelajaran', 'element', 'capaian_pembelajaran', 'capaian_pembelajaran_redaksi')
                ->get();

            if ($finalData->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data tidak ditemukan untuk kombinasi fase, mata pelajaran, dan elemen capaian yang diberikan',
                    'data' => [],
                ]);
            }

            $capaianPembelajaran = $finalData[0]->capaian_pembelajaran_redaksi;
            $capaianPembelajaranRedaksi = $finalData->pluck('capaian_pembelajaran')->implode(' ');

            // Generate prompt untuk essay exercise
            $prompt             = $this->generateExercisesEssayPrompt($faseKelas, $mataPelajaran, $element, $capaianPembelajaran, $capaianPembelajaranRedaksi,  $jumlahSoal, $notes);
            $resMessage         = $this->openAI->sendMessage($prompt);
            $parsedResponse     = json_decode($resMessage, true);

            $user = $request->user();
            $parsedResponse['informasi_umum']['nama_latihan'] = $exerciseName;
            $parsedResponse['informasi_umum']['penyusun'] = $user->name;
            $parsedResponse['informasi_umum']['instansi'] = $user->school_name;
            $parsedResponse['informasi_umum']['tahun_penyusunan'] = Date('Y');

            // Simpan hasil latihan ke database menggunakan metode create
            $exerciseHistory = ExerciseV2Histories::create([
                'name' => $exerciseName,
                'phase' => $faseKelas,
                'subject' => $mataPelajaran,
                'element' => $element,
                'number_of_question' => $jumlahSoal,
                'type' => 'essay',
                'notes' => $notes,
                'output_data' => $parsedResponse,
                'user_id' => auth()->id(),
            ]);

            $parsedResponse['id'] = $exerciseHistory->id;

            return response()->json([
                'status' => 'success',
                'message' => 'Latihan esai berhasil dihasilkan',
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

    public function generateChoice(Request $request)
    {
        try {
            // Validasi input
            $request->validate([
                'name' => 'required|string',
                'phase' => 'required|string',
                'subject' => 'required|string',
                'element' => 'required|string',
                'number_of_questions' => 'required|integer|min:1|max:15',
                'notes' => 'nullable|string',
            ]);

            // Retrieve the authenticated user
            $user = $request->user();

            // Check if the user is active
            if ($user->is_active === 0) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'User tidak aktif. Anda tidak dapat membuat latihan soal.',
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

            // Ambil data dari permintaan
            $exerciseName   = $request->input('name');
            // $faseKelas      = $request->input('phase');
            $faseRaw        = $request->input('phase');
            $faseSplit      = explode('|', $faseRaw);
            $faseKelas      = trim($faseSplit[0]);
            $kelas          = trim($faseSplit[1]);
            $mataPelajaran  = $request->input('subject');
            $element        = $request->input('element');
            $jumlahSoal     = $request->input('number_of_questions');
            $notes          = $request->input('notes') ? $request->input('notes') : '';

            // Capaian Pembelajaran
            $finalData = CapaianPembelajaran::where('fase', $faseKelas)
                ->where('mata_pelajaran', $mataPelajaran)
                ->where('element', $element)
                ->select('fase', 'mata_pelajaran', 'element', 'capaian_pembelajaran', 'capaian_pembelajaran_redaksi')
                ->get();

            if ($finalData->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data tidak ditemukan untuk kombinasi fase, mata pelajaran, dan elemen capaian yang diberikan',
                    'data' => [],
                ]);
            }

            $capaianPembelajaran = $finalData[0]->capaian_pembelajaran_redaksi;
            $capaianPembelajaranRedaksi = $finalData->pluck('capaian_pembelajaran')->implode(' ');

            // Generate prompt untuk essay exercise
            $prompt             = $this->generateExercisesChoicePrompt($faseKelas, $mataPelajaran, $element, $capaianPembelajaran, $capaianPembelajaranRedaksi,  $jumlahSoal, $notes);
            $resMessage         = $this->openAI->sendMessage($prompt);
            $parsedResponse     = json_decode($resMessage, true);

            $user = $request->user();
            $parsedResponse['informasi_umum']['nama_latihan'] = $exerciseName;
            $parsedResponse['informasi_umum']['penyusun'] = $user->name;
            $parsedResponse['informasi_umum']['instansi'] = $user->school_name;
            $parsedResponse['informasi_umum']['tahun_penyusunan'] = Date('Y');

            // Loop through each question
            foreach ($parsedResponse['soal_pilihan_ganda'] as $question) {
                $correct_options[] = $question['correct_option'];
            }

            // Populate the 'kunci_jawaban' array with the array of correct options
            $parsedResponse['kunci_jawaban'] = $correct_options;
            $parsedResponse['generated_num'] = count($parsedResponse['soal_pilihan_ganda']);

            // Simpan hasil latihan ke database menggunakan metode create
            $exerciseHistory = ExerciseV2Histories::create([
                'name' => $exerciseName,
                'phase' => $faseKelas,
                'subject' => $mataPelajaran,
                'element' => $element,
                'number_of_question' => $jumlahSoal,
                'type' => 'multiple_choice',
                'notes' => $notes,
                'output_data' => $parsedResponse,
                'user_id' => auth()->id(),
            ]);

            $parsedResponse['id'] = $exerciseHistory->id;

            return response()->json([
                'status' => 'success',
                'message' => 'Latihan pilihan ganda berhasil dihasilkan',
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
            $exerciseHistoriesId    = $request->input('id');
            $exerciseHistories      = ExerciseV2Histories::find($exerciseHistoriesId);

            if ($exerciseHistories->type == 'multiple_choice') {
                $templatePath   = public_path('word_template/Soal_Pilihan_V2_Template.docx');
                $docxTemplate   = new DocxTemplate($templatePath);
                $outputPath     = public_path('word_output/Soal_Pilihan_V2_' . auth()->id() . '_' . md5(time() . '' . rand(1000, 9999)) . '.docx');
            } else if ($exerciseHistories->type == 'essay') {
                $templatePath   = public_path('word_template/Soal_Essay_V2_Template.docx');
                $docxTemplate   = new DocxTemplate($templatePath);
                $outputPath     = public_path('word_output/Soal_Essay_V2_' . auth()->id() . '_' . md5(time() . '' . rand(1000, 9999)) . '.docx');
            } else {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Data tidak ditemukan!',
                ], 500);
            }

            $data = $exerciseHistories->output_data;
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
            $exerciseHistories = $user->exerciseV2History()
                ->select(['id', 'name', 'phase', 'subject', 'type', 'notes', 'created_at', 'updated_at', 'user_id'])
                ->get();

            if ($exerciseHistories->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak ada riwayat soal latihan untuk akun anda!',
                    'data' => [
                        'generated_num' => 0,
                        'items' => [],
                    ],
                ], 200);
            }

            // Calculate the total generated syllabuses by the user
            $generatedNum = $exerciseHistories->count();

            // Return the response with syllabus histories data
            return response()->json([
                'status' => 'success',
                'message' => 'Riwayat soal latihan ditampilkan',
                'data' => [
                    'generated_num' => $generatedNum,
                    'items' => $exerciseHistories,
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
            $exerciseHistories = $user->exerciseV2History()->find($id);

            if (!$exerciseHistories) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Riwayat hasil latihan soal tidak tersedia di akun ini!',
                    'data' => null,
                ], 404);
            }

            // Return the response with Material history data
            return response()->json([
                'status' => 'success',
                'message' => 'Exercise history retrieved successfully',
                'data' => $exerciseHistories,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage(),
                'data' => json_decode($e->getMessage(), true),
            ], 500);
        }
    }

    private function generateExercisesEssayPrompt($faseKelas, $mataPelajaran, $element, $capaianPembelajaran, $capaianPembelajaranRedaksi, $jumlahSoal, $notes)
    {
        $prompt = '';
        $prompt .= '
        Anda adalah seorang guru yang berdedikasi untuk menyediakan latihan esai berkualitas bagi siswa Anda.
        Mata pelajaran yang Anda ajarkan adalah ' . $mataPelajaran . ' untuk tingkat kelas ' . $faseKelas . '
        Saat ini, Anda diminta untuk membuat latihan esai dengan memperhatikan catatan khusus berikut: ' . $notes . '

        Tujuan latihan ini adalah untuk membantu siswa memahami dan menguasai konsep yang diajarkan dalam pelajaran ini.
        Oleh karena itu, Anda perlu membuat latihan yang terstruktur dengan baik dan mudah dipahami oleh siswa.
        
        Elemen yang akan dibahas dalam latihan ini adalah: ' . $element . '
        Capaian pembelajaran yang diharapkan dari latihan ini adalah: ' . $capaianPembelajaran . '
        Capaian pembelajaran redaksi sesuai kurikulum kementrian pendidikan adalah: ' . $capaianPembelajaranRedaksi . '
        
        Penjelasan Template Format JSON:
        - penyusun: Diisi dengan nama penyusun latihan.
        - instansi: Diisi dengan nama instansi atau sekolah penyusun.
        - tahun_penyusunan: Diisi dengan tahun penyusunan latihan.
        - jenjang_sekolah: Diisi dengan tingkat jenjang sekolah sesuai fase (misalnya SD, SMP, SMA).
        - mata_pelajaran: Diisi dengan nama mata pelajaran.
        - fase_kelas: Diisi dengan fase atau tingkat kelas.
        - topik: Diisi dengan topik utama dari latihan, berbentuk 1 paragraf/alinea.
        - alokasi_waktu: Diisi dengan alokasi waktu yang diperlukan untuk menyelesaikan latihan.
        - kompetensi_awal: Diisi dengan kompetensi awal yang diperlukan sebelum mengikuti latihan, berbentuk 1 paragraf/alinea.
        - soal_essay: Diisi dengan array daftar soal essay.
            -- question: Diisi dengan pertanyaan soal essay.
            -- instructions: Diisi dengan instruksi untuk menjawab soal.
            -- kriteria_penilaian: Diisi dengan kriteria penilaian untuk jawaban, berbentuk beberapa paragraf.
        
        Perhatian: Mohon jawab dengan format template JSON berikut dan isi sesuai Penjelasan Template Format JSON ya:
        {
            "informasi_umum": {
                "penyusun": "",
                "instansi": "",
                "tahun_penyusunan": "",
                "jenjang_sekolah": "",
                "fase_kelas": "' . $faseKelas . '",
                "mata_pelajaran": "' . $mataPelajaran . '",
                "element": "' . $element . '",
                "capaian_pembelajaran": "' . $capaianPembelajaran . '",
                "capaian_pembelajaran_redaksi": "' . $capaianPembelajaranRedaksi . '",
                "topik": "(Berbentuk 1 Paragraf/Alinea)",
                "kompetensi_awal": "(Berbentuk 1 Paragraf/Alinea)",
                "alokasi_waktu": "",
            },
            "soal_essay": [
                {
                    "question": "",
                    "instructions": "",
                    "kriteria_penilaian": ["", "", ""]
                },
                // Lanjutkan pola ini sampai jumlah soal_essay mencapai ' . $jumlahSoal . '
            ]
        }

        Jumlah soal yang diminta: ' . $jumlahSoal . ' dan Jangan sampai anda memngembalikan JSON template kosongan atau data dummy, tolong dengan real isi!';

        return $prompt;
    }

    private function generateExercisesChoicePrompt($faseKelas, $mataPelajaran, $element, $capaianPembelajaran, $capaianPembelajaranRedaksi, $jumlahSoal, $notes)
    {
        $prompt = '';
        $prompt .= '
        Anda adalah seorang guru yang berdedikasi untuk menyediakan latihan esai berkualitas bagi siswa Anda.
        Mata pelajaran yang Anda ajarkan adalah ' . $mataPelajaran . ' untuk tingkat kelas ' . $faseKelas . '
        Saat ini, Anda diminta untuk membuat latihan esai dengan memperhatikan catatan khusus berikut: ' . $notes . '

        Tujuan latihan ini adalah untuk membantu siswa memahami dan menguasai konsep yang diajarkan dalam pelajaran ini.
        Oleh karena itu, Anda perlu membuat latihan yang terstruktur dengan baik dan mudah dipahami oleh siswa.
        
        Elemen yang akan dibahas dalam latihan ini adalah: ' . $element . '
        Capaian pembelajaran yang diharapkan dari latihan ini adalah: ' . $capaianPembelajaran . '
        Capaian pembelajaran redaksi sesuai kurikulum kementrian pendidikan adalah: ' . $capaianPembelajaranRedaksi . '
        
        Penjelasan Template Format JSON:
        - penyusun: Diisi dengan nama penyusun latihan.
        - instansi: Diisi dengan nama instansi atau sekolah penyusun.
        - tahun_penyusunan: Diisi dengan tahun penyusunan latihan.
        - jenjang_sekolah: Diisi dengan tingkat jenjang sekolah sesuai fase (misalnya SD, SMP, SMA).
        - mata_pelajaran: Diisi dengan nama mata pelajaran.
        - fase_kelas: Diisi dengan fase atau tingkat kelas.
        - topik: Diisi dengan topik utama dari latihan, berbentuk 1 paragraf/alinea.
        - alokasi_waktu: Diisi dengan alokasi waktu yang diperlukan untuk menyelesaikan latihan.
        - kompetensi_awal: Diisi dengan kompetensi awal yang diperlukan sebelum mengikuti latihan, berbentuk 1 paragraf/alinea.
        - soal_pilihan_ganda: Diisi dengan array daftar soal pilihan ganda.
            -- question: Diisi dengan pertanyaan soal pilihan ganda.
            -- options: Diisi dengan opsi untuk menjawab soal.
            -- correct_option: Diisi dengan jawaban yang tepat, berbentuk Abjad!.
        
        Perhatian: Mohon jawab dengan format template JSON berikut dan isi sesuai Penjelasan Template Format JSON ya:
        {
            "informasi_umum": {
                "penyusun": "",
                "instansi": "",
                "tahun_penyusunan": "",
                "jenjang_sekolah": "",
                "fase_kelas": "' . $faseKelas . '",
                "mata_pelajaran": "' . $mataPelajaran . '",
                "element": "' . $element . '",
                "capaian_pembelajaran": "' . $capaianPembelajaran . '",
                "capaian_pembelajaran_redaksi": "' . $capaianPembelajaranRedaksi . '",
                "topik": "(Berbentuk 1 Paragraf/Alinea)",
                "kompetensi_awal": "(Berbentuk 1 Paragraf/Alinea)",
                "alokasi_waktu": "",
            },
            "soal_pilihan_ganda": [
                {
                    "question": "",
                    "options": {
                        "a": "",
                        "b": "",
                        "c": "",
                        "d": ""
                    },
                    "correct_option": ""
                },
                // Lanjutkan pola ini sampai jumlah soal_essay mencapai ' . $jumlahSoal . '
            ]
        }

        Jumlah soal yang diminta: ' . $jumlahSoal . ' dan Jangan sampai anda memngembalikan JSON template kosongan atau data dummy, tolong dengan real isi!';

        return $prompt;
    }
}

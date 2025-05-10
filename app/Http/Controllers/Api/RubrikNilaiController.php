<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\RubrikNilaiHistories;
use App\Models\CapaianPembelajaran;
use App\Models\CreditLog;
use App\Models\ModuleCreditCharge;
use App\Services\OpenAIService;
use icircle\Template\Docx\DocxTemplate;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\RichText\Run;

class RubrikNilaiController extends Controller
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
                'name'  => 'required',
                'phase' => 'required',
                'subject' => 'required',
                'element' => 'required',
                'notes' => 'nullable',
            ]);

            // Retrieve the authenticated user
            $user = $request->user();

            // Check if the user is active
            if ($user->is_active === 0) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'User tidak aktif. Anda tidak dapat membuat modul ajar.',
                ], 400);
            }

            // Check if the user has less than the limit generate
            $moduleCredit = ModuleCreditCharge::where('slug', 'rubrik-nilai')->first();
            $creditCharge = $moduleCredit->credit_charged_generate ?? 1;
            if ($user->credit < $creditCharge) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Anda telah mencapai batas maksimal untuk riwayat rubrik nilai.',
                    'data' => [
                        'user_credit' => $user->credit,
                        'generated_all_num' => $user->generateAllSum(),
                        'limit_all_num' => $user->limit_generate
                    ],
                ], 400);
            }

            // Parameters
            $namaRubrikNilai  = $request->input('name');
            $faseRaw          = $request->input('phase');
            $faseSplit        = explode('|', $faseRaw);
            $faseKelas        = trim($faseSplit[0]);
            $kelas            = trim($faseSplit[1]);
            $mataPelajaran    = $request->input('subject');
            $elemen           = $request->input('element');
            $addNotes         = $request->input('notes');

            // Get Capaian Pembelajaran
            $finalData = CapaianPembelajaran::where('fase', $faseKelas)
                ->where('mata_pelajaran', $mataPelajaran)
                ->where('element', $elemen)
                ->select('fase', 'mata_pelajaran', 'element', 'capaian_pembelajaran')
                ->get();

            if (!$finalData) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Data tidak ditemukan untuk kombinasi fase, mata pelajaran, dan elemen capaian yang diberikan',
                    'data' => [],
                ], 400);
            }

            $capaianPembelajaran = $finalData[0]->capaian_pembelajaran;

            // Create prompt
            $prompt = $this->prompt($faseKelas, $mataPelajaran, $elemen, $capaianPembelajaran, $addNotes);

            // Send the message to OpenAI
            $resMessage = $this->openAI->sendMessage($prompt);
            $parsedResponse = json_decode($resMessage, true);

            // Map Fase Kelas
            $faseToKelas = [
                'Fase A' => 'Kelas 1 - 2 SD',
                'Fase B' => 'Kelas 3 - 4 SD',
                'Fase C' => 'Kelas 5 - 6 SD',
                'Fase D' => 'Kelas 7 - 9 SMP',
                'Fase E' => 'Kelas 10 SMA',
                'Fase F' => 'Kelas 11 - 12 SMA'
            ];

            $kelas = isset($faseToKelas[$faseKelas]) ? "{$faseKelas} ({$faseToKelas[$faseKelas]})" : $faseKelas;

            // Add additional information
            $parsedResponse['informasi_umum']['nama_rubrik_nilai'] = $namaRubrikNilai;
            $parsedResponse['informasi_umum']['penyusun'] = $user->name;
            $parsedResponse['informasi_umum']['jenjang_sekolah'] = $user->school_name;
            $parsedResponse['informasi_umum']['fase_kelas'] = $kelas;
            $parsedResponse['informasi_umum']['mata_pelajaran'] = $mataPelajaran;
            $parsedResponse['informasi_umum']['element'] = $elemen;

            $insertData = RubrikNilaiHistories::create([
                'name' => $namaRubrikNilai,
                'phase' => $faseKelas,
                'subject' => $mataPelajaran,
                'element' => $elemen,
                'notes' => $addNotes,
                'generate_output' => $parsedResponse,
                'user_id' => $user->id,
            ]);

            // Decrease user's credit
            $user->decrement('credit', $creditCharge);

            // Credit Logging
            CreditLog::create([
                'user_id' => $user->id,
                'amount' => -$creditCharge,
                'description' => 'Generate Rubrik Nilai',
            ]);

            // Add ID to response
            $parsedResponse['id'] = $insertData->id;

            return response()->json([
                'status' => 'success',
                'message' => 'Rubrik nilai berhasil dihasilkan',
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
            $templatePath   = public_path('word_template/Rubrik_Nilai_Template.docx');
            $docxTemplate   = new DocxTemplate($templatePath);
            $outputPath     = public_path('word_output/Rubrik_Nilai_' . auth()->id() . '_' . md5(time() . '' . rand(1000, 9999)) . '.docx');

            $rubrikNilaiId  = $request->input('id');
            $rubrikNilai    = RubrikNilaiHistories::find($rubrikNilaiId);

            $data = $rubrikNilai->generate_output;
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

            // Get hint histories for the authenticated user
            $rubrikNilaiHistories = $user->rubrikNilaiHistory()
                ->select(['id', 'name', 'phase', 'subject', 'element', 'notes', 'user_id', 'created_at', 'updated_at'])
                ->get();

            if ($rubrikNilaiHistories->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak ada riwayat rubrik nilai untuk akun anda!',
                    'data' => [
                        'generated_num' => 0,
                        'items' => [],
                    ],
                ], 200);
            }

            // Calculate the total generated hints by the user
            $generatedNum = $rubrikNilaiHistories->count();

            // Return the response with hint histories data
            return response()->json([
                'status' => 'success',
                'message' => 'Riwayat rubrik nilai berhasil ditampilkan!',
                'data' => [
                    'generated_num' => $generatedNum,
                    'items' => $rubrikNilaiHistories,
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

            // Get a specific hint history by ID for the authenticated user
            $rubrikNilaiHistories = $user->rubrikNilaiHistory()->find($id);

            if (!$rubrikNilaiHistories) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Riwayat hasil rubrik nilai tidak tersedia pada akun ini!',
                    'data' => null,
                ], 404);
            }

            // Return the response with hint history data
            return response()->json([
                'status' => 'success',
                'message' => 'Rubrik nilai history retrieved successfully',
                'data' => $rubrikNilaiHistories,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage(),
                'data' => json_decode($e->getMessage(), true),
            ], 500);
        }
    }

    public function prompt($kelas, $mataPelajaran, $elemen, $addNotes)
    {
        $prompt = '';
        $prompt .= '
        Buatlah objek JSON untuk Rubrik Nilai berdasarkan parameter berikut:

        - fase_kelas: ' . $kelas . '
        - mata_pelajaran: ' . $mataPelajaran . '
        - element: ' . $elemen . '

        Parameter objek JSON tidak perlu menjadi output pada struktur JSON.

        Buatlah Rubrik Nilai yang berisikan level_pencapaian, deskripsi, dan indikasi_pencapaian. Catatan tambahan dalam bahasa Indonesia: ' . $addNotes . '

        Rubrik Nilai merupakan kumpulan kriteria penilaian yang digunakan untuk menilai kemampuan peserta didik dalam mata pelajaran tertentu dengan elemen tertentu.

        Secara struktur, komponen dari Rubrik Nilai adalah sebagai berikut:
        - pencapaian : Array yang berisikan level_pencapaian, deskripsi, dan indikasi_pencapaian.
               -- level_pencapaian : Tingkatan penguasaan kompetensi atau kemampuan peserta didik dalam memenuhi kriteria penilaian dengan kategori tertentu.
               -- deskripsi : Menjelaskan apa yang dapat dilakukan oleh peserta didik pada level pencapaian tertentu.
               -- indikasi_pencapaian : Indikator konkret atau contoh perilaku peserta didik yang menunjukkan bahwa mereka berada pada level tertentu.

        Isi data level_pencapaian, deskripsi, dan indikasi_pencapaian berkaitan dengan fase kelas, mata pelajaran, dan elemen yang diberikan.

        Struktur data Rubrik Nilai minimal memiliki 5 pencapaian dengan 5 kategori pada level_pencapaian. Level pencapaian menggunakan kata-kata yang mendeskripsikan kemampuan peserta didik secara jelas, positif, dan objektif sesuai tingkat pencapaiannya.
        Deskripsi menggunakan kalimat yang menjelaskan apa yang dapat dilakukan oleh peserta didik pada level pencapaian tertentu.
        Indikasi pencapaian menggunakan kalimat yang menjelaskan perilaku peserta didik yang menunjukkan bahwa mereka berada pada level tertentu.

        Struktur JSON yang harus dihasilkan:
        {
            "pencapaian": [
                {
                    "level_pencapaian": "{Level Pencapaian}",
                    "deskripsi": ["{Deskripsi 1}", "{Deskripsi 2}", "{Deskripsi 3}", "{Deskripsi 4}", "{Deskripsi 5}"],
                    "indikasi_pencapaian": ["{Indikasi Pencapaian 1}", "{Indikasi Pencapaian 2}", "{Indikasi Pencapaian 3}", "{Indikasi Pencapaian 4}", "{Indikasi Pencapaian 5}"]
                }
            ]
        }
        Pastikan mengisi semua field yang ada di atas dengan data dan format yang benar. Terima kasih atas kerja sama Anda.
        ';

        return $prompt;
    }
}

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
            $faseRaw            = $request->input('phase');
            $faseSplit          = explode('|', $faseRaw);
            $fase               = trim($faseSplit[0]);
            $kelas              = trim($faseSplit[1]);
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
                    'message' => 'Data tidak ditemukan untuk kombinasi fase, mata pelajaran, dan elemen capaian yang diberikan',
                    'data' => [],
                ]);
            }

            $capaianPembelajaran = $finalData[0]->capaian_pembelajaran_redaksi;
            $capaianPembelajaranTahun = $finalData->pluck('capaian_pembelajaran')->implode(' ');

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

            $fileName   = 'Alur_Tujuan_Pembelajaran_' . auth()->id() . '_' . md5(time() . '' . rand(1000, 9999)) . '.xlsx';
            $filePath   = public_path('excel_output/' . $fileName);
            $fileUrl    = url('storage/excel_output/' . $fileName);

            if (!file_exists(public_path('excel_output'))) {
                mkdir(public_path('excel_output'), 0777, true);
            }

            Excel::store(new ATPExport($data), 'excel_output/' . $fileName, 'public');

            return response()->json([
                'status' => 'success',
                'message' => 'Dokumen Excel berhasil dibuat',
                'data' => [
                    'output_path' => $filePath,
                    'download_url' => $fileUrl
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
        Buatlah objek JSON untuk alur tujuan pembelajaran berdasarkan parameter berikut:

        - fase: ' . $fase . '
        - mata_pelajaran: ' . $mataPelajaran . '
        - elemen: ' . $elemen . '
        - capaian_pembelajaran: ' . $capaianPembelajaran . '
        - capaian_pembelajaran_per_tahun: ' . $capaianPembelajaranTahun . '
        - pekan: ' . $pekan . '
        
        Buatlah array bernama "alur" di mana jumlah tujuan pembelajaran sesuai dengan jumlah minggu (pekan) yang ditentukan. Setiap tujuan pembelajaran harus didasarkan pada capaian_pembelajaran dan elemen. Catatan tambahan dalam bahasa Indonesia: ' . $deskripsiNotes . '
        
        Tujuan Pembelajaran (TP) menggambarkan pencapaian tiga aspek kompetensi (pengetahuan, keterampilan, sikap) yang perlu dikembangkan melalui satu atau lebih kegiatan pembelajaran. Tujuan pembelajaran diurutkan secara kronologis berdasarkan urutan pembelajaran dari waktu ke waktu yang menjadi prasyarat untuk mencapai hasil pembelajaran (Capaian Pembelajaran - CP).

        Secara operasional, komponen Tujuan Pembelajaran (tujuan_pembelajaran) harus mencakup:
        - Kompetensi: Kemampuan yang dapat ditunjukkan oleh siswa atau ditampilkan dalam bentuk produk yang menunjukkan bahwa siswa telah berhasil mencapai tujuan pembelajaran.
        - Konten: Pengetahuan inti atau konsep utama yang perlu dipahami pada akhir unit pembelajaran.
        - Variasi: Menggambarkan keterampilan berpikir kreatif, kritis, dan tingkat tinggi yang perlu dikuasai siswa untuk mencapai tujuan pembelajaran, seperti mengevaluasi, menganalisis, memprediksi, menciptakan, dll.

        Daftar Profil Pelajar Pancasila:
        - Beriman, Bertakwa kepada Tuhan YME, dan Berakhlak Mulia: Pelajar yang memiliki akhlak baik dalam hubungannya dengan Tuhan, sesama manusia, dan lingkungan, serta menunjukkan sikap beragama, pribadi, sosial, dan kenegaraan yang mulia.
        - Berkebinekaan Global: Pelajar yang menghargai dan mempertahankan budaya serta identitas lokal sambil terbuka terhadap budaya lain, dengan kemampuan komunikasi dan refleksi interkultural yang baik.
        - Bergotong Royong: Pelajar yang aktif dalam kolaborasi, berbagi, dan memiliki kepedulian terhadap keberhasilan bersama, mampu bekerja sama untuk mencapai tujuan bersama dengan semangat gotong royong.
        - Mandiri: Pelajar yang bertanggung jawab atas proses dan hasil belajarnya, menunjukkan kesadaran diri dan kemampuan regulasi diri dalam mengelola belajar dan tantangan pribadi.
        - Bernalar Kritis: Pelajar yang mampu memproses informasi secara objektif, menganalisis dan mengevaluasi data, serta membuat keputusan berdasarkan refleksi dan penalaran kritis.
        - Kreatif: Pelajar yang mampu menghasilkan gagasan dan karya yang orisinal, memiliki kemampuan untuk berinovasi dan menciptakan solusi baru yang bermanfaat dan berdampak.

        Sertakan bidang berikut untuk setiap alur tujuan:
        - no: Nomor urut tujuan pembelajaran.
        - tujuan_pembelajaran: Tujuan Pembelajaran yang menentukan apa yang harus dicapai siswa setiap minggu. Tujuan Pembelajaran (TP) menggambarkan pencapaian tiga aspek kompetensi (pengetahuan, keterampilan, sikap) yang perlu dikembangkan melalui satu atau lebih kegiatan pembelajaran. Tujuan pembelajaran diurutkan secara kronologis berdasarkan urutan pembelajaran dari waktu ke waktu yang menjadi prasyarat untuk mencapai hasil pembelajaran (Capaian Pembelajaran - CP). Panjang minimum tujuan_pembelajaran adalah 3 kalimat ya, tulis dalam bentuk narasi akademis.
        - kata_frase_kunci: Kata/Frase Kunci terkait dengan tujuan pembelajaran.
        - profil_pelajar_pancasila: Karakteristik Profil Pelajar Pancasila yang ingin dikembangkan melalui tujuan tersebut, dengan maksimal 2 dari 6 profil yang sesuai per minggu.
        - glosarium: Buat daftar istilah secara alfabetis dalam domain pengetahuan tertentu dengan definisi untuk istilah-istilah tersebut, Dipisah dengan ; antara gabungan istilah dan penjelasan glorasium ya.

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
                    "tujuan_pembelajaran": "{Tujuan Pembelajaran Pekan 1, minimal 3 sampai 5 kalimat per tujuan pembelajaran, agar detail ya}",
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
                    "tujuan_pembelajaran": "{Tujuan Pembelajaran Pekan 2, minimal 3 sampai 5 kalimat per tujuan pembelajaran, agar detail ya}",
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

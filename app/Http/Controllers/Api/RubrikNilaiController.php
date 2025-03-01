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
            $namaModulAjar  = $request->input('name');
            $faseRaw        = $request->input('phase');
            $faseSplit      = explode('|', $faseRaw);
            $faseKelas      = trim($faseSplit[0]);
            $kelas          = trim($faseSplit[1]);
            $mataPelajaran  = $request->input('subject');
            $elemen         = $request->input('element');
            $addNotes       = $request->input('notes');

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
            $parsedResponse['informasi_umum']['nama_modul_ajar'] = $namaModulAjar;
            $parsedResponse['informasi_umum']['penyusun'] = $user->name;
            $parsedResponse['informasi_umum']['jenjang_sekolah'] = $user->school_name;
            $parsedResponse['informasi_umum']['fase_kelas'] = $kelas;
            $parsedResponse['informasi_umum']['mata_pelajaran'] = $mataPelajaran;
            $parsedResponse['informasi_umum']['element'] = $elemen;
            $parsedResponse['informasi_umum']['capaian_pembelajaran'] = $capaianPembelajaran;

            $insertData = RubrikNilaiHistories::create([
                'name' => $namaModulAjar,
                'phase' => $faseKelas,
                'subject' => $mataPelajaran,
                'element' => $elemen,
                'notes' => $addNotes,
                'output_data' => $parsedResponse,
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
            $rubrikNilai    = RubrikNilaiHistories::find($modulAjarId);

            $data = $rubrikNilai->output_data;
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

            if (!$modulAjarHistories) {
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

    public function prompt($kelas, $mataPelajaran, $elemen, $capaianPembelajaran, $addNotes)
    {
        $prompt = '';
        $prompt .= '
        Buatlah objek JSON untuk Modul Ajar berdasarkan parameter berikut:

        - fase_kelas: ' . $kelas . '
        - mata_pelajaran: ' . $mataPelajaran . '
        - element: ' . $elemen . '
        - capaian_pembelajaran: ' . $capaianPembelajaran . '

        Buatlah Modul ajar dimana array komponen_pembelajaran, tujuan_kegiatan_pembelajaran, pemahaman_bermakna, pertanyaan_pemantik, dan kompetensi_dasar isinya akan berdasarkan mata_pelajaran, capaian_pembelajaran, serta elemen. Catatan tambahan dalam bahasa Indonesia: ' . $addNotes . '

        Modul Ajar merupakan materi pembelajaran terstruktur yang digunakan sebagai alat bantu guru dalam proses pengajaran dan proses pembelajaran siswa. Modul Ajar dirancang sedemikian rupa agar dapat mencapai target Capaian Pembelajaran (CP).

        Secara struktur, komponen dari Modul Ajar adalah sebagai berikut:
        - alokasi_waktu : Alokasi waktu pada bagian informasi_umum merupakan waktu yang dibutuhkan untuk menyelesaikan seluruh Modul Ajar seperti materi dan aktivitas pembelajaran.
          //Perhatikan: alokasi_waktu memiliki struktur format: berapa pekan, berapa JP, berapa pertemuan. Asumsikan bahwa rasio pekan : JP : pertemuan adalah 1 : 3 : 1.
        - kompetensi_dasar : Array yang berisikan rincian materi dalam Modul ajar.
               -- nama_kompetensi_dasar : Bagian dari array kompetensi_dasar yang berisi nama materi pembelajaran dengan acuan dari mata_pelajaran, elemen, dan capaian_pembelajaran.
               -- materi_pembelajaran : Bagian dari array kompetensi_dasar yang berisi materi pembelajaran yang dibutuhkan untuk menyelesaikan kompetensi dasar.
                         --- materi : Bagian dari array materi_pembelajaran yang merupakan nama spesifik dari nama_kompetensi_dasar.
                         --- tujuan_pembelajaran_materi : Tujuan yang menjadi acuan peserta didik dianggap telah memahami materi pembelajaran.
                         --- indikator : Hasil akhir dari tujuan_pembelajaran_materi.
                         --- alokasi_waktu : Mengambil jatah alokasi_waktu pada informasi_umum. Total alokasi_waktu pada array materi_pembelajaran harus sesuai dengan alokasi_waktu pada informasi_umum.
        - glosarium_materi : Memiliki 10 item yang diurutkan secara alfabet. Setiap item pada Glosarium Materi harus berkaitan dengan mata_pelajaran, capaian_pembelajaran, serta elemen.
        - daftar_pustaka : Memiliki 5 item yang diurutkan secara alfabet. Daftar pustaka merupakan referensi yang digunakan untuk materi pada Modul Ajar. Setiap item pada Daftar Pustaka harus lengkap sesuai tata cara penulisan "petajukobit" yaitu penulis, tahun, judul, kota, penerbit. Pastikan setiap item pada Daftar Pustaka adalah referensi nyata bukan fiktif!

        Daftar Profil Pelajar Pancasila:
        - Beriman, Bertakwa kepada Tuhan YME, dan Berakhlak Mulia: Pelajar yang memiliki akhlak baik dalam hubungannya dengan Tuhan, sesama manusia, dan lingkungan, serta menunjukkan sikap beragama, pribadi, sosial, dan kenegaraan yang mulia.
        - Berkebinekaan Global: Pelajar yang menghargai dan mempertahankan budaya serta identitas lokal sambil terbuka terhadap budaya lain, dengan kemampuan komunikasi dan refleksi interkultural yang baik.
        - Bergotong Royong: Pelajar yang aktif dalam kolaborasi, berbagi, dan memiliki kepedulian terhadap keberhasilan bersama, mampu bekerja sama untuk mencapai tujuan bersama dengan semangat gotong royong.
        - Mandiri: Pelajar yang bertanggung jawab atas proses dan hasil belajarnya, menunjukkan kesadaran diri dan kemampuan regulasi diri dalam mengelola belajar dan tantangan pribadi.
        - Bernalar Kritis: Pelajar yang mampu memproses informasi secara objektif, menganalisis dan mengevaluasi data, serta membuat keputusan berdasarkan refleksi dan penalaran kritis.
        - Kreatif: Pelajar yang mampu menghasilkan gagasan dan karya yang orisinal, memiliki kemampuan untuk berinovasi dan menciptakan solusi baru yang bermanfaat dan berdampak.

        Sertakan bidang berikut untuk setiap bagian dari Modul Ajar sebagai berikut:
        - kompetensi_awal : Persyaratan yang perlu dikuasai peserta didik sebelum mengikuti pembelajaran.
        - profil_pelajar_pancasila: Karakteristik Profil Pelajar Pancasila yang ingin dikembangkan melalui tujuan pembelajaran dengan mengambil maksimal 3 dari 6 daftar Profil Pelajar Pancasila.
        - target_peserta_didik : Tujuan yang dicapai oleh peserta didik setelah mengikuti pembelajaran.
        - model_pembelajaran : Metode yang digunakan untuk menyampaikan materi pembelajaran. Misalkan menggunakan tugas proyek, pendekatan tugas, dan sejenisnya.
        - sumber_belajar : Sumber materi yang digunakan dalam pembelajaran. Berikan dalam bentuk paragraf.
        - lembar_kerja_peserta_didik : Media yang digunakan peserta didik untuk mengerjakan materi pembelajaran seperti buku catatan, lembar kerja siswa, dan sejenisnya. Berikan dalam bentuk paragraf.
        - pertanyaan_pemantik : Pertanyaan untuk peserta didik yang berkaitan dengan mata_pelajaran, element, dan capaian_pembelajaran.
        - kompetensi_dasar : Array yang berisikan rincian materi dalam Modul ajar.
                -- nama_kompetensi_dasar : Bagian dari array kompetensi_dasar yang berisi nama materi pembelajaran dengan acuan dari mata_pelajaran, elemen, dan capaian_pembelajaran.
                -- materi_pembelajaran : BAgian dari array kompetensi_dasar yang merupakan materi pembelajaran yang akan dipelajari peserta didik.
                    --- materi : Bagian dari array materi_pembelajaran yang merupakan nama spesifik dari nama_kompetensi_dasar.
                    --- tujuan_pembelajaran_materi : Tujuan yang menjadi acuan peserta didik dianggap telah memahami materi pembelajaran.
                    --- indikator : Hasil akhir dari tujuan_pembelajaran_materi.
                    --- nilai_karakter : Karakter yang diperlukan oleh peserta didik untuk mengikuti materi pembelajaran.
                    --- kegiatan_pembelajaran : Kegiatan yang akan dilakukan peserta didik saat mengikuti materi_pembelajaran.
                    --- alokasi_waktu : Perhitungan waktu yang diperlukan peserta didik untuk mengikuti materi pembelajaran.
                    --- penilaian : Penilaian yang akan diberikan kepada peserta didik.


        Array "tujuan_kegiatan_pembelajaran" sebagai berikut:
        - tujuan_pembelajaran_pertemuan : Tujuan pembelajaran pada setiap pertemuan tanpa menuliskan pertemuan ke berapa. Data untuk tujuan_pembelajaran_pertemuan menyesuaikan jumlah pertemuan dari "alokasi_waktu".
        - tujuan_pembelajaran_topik : Hasil yang diharapkan dapat dicapai oleh peserta didik setelah mengikuti pembelajaran setiap pertemuan.

        {
            "informasi_umum": {
                "alokasi_waktu": "(Berupa berapa pekan, berapa JP, berapa pertemuan)", //Pastikan sesuai dengan format.
                "kompetensi_awal": "{Kompetensi Awal}",
                "profil_pelajar_pancasila": "{Profil Pelajar Pancasila}", // Berupa string
                "target_peserta_didik": "{Target Peserta Didik}",
                "model_pembelajaran": "{Model Pembelajaran}"
            },
            "sarana_dan_prasarana": {
                "sumber_belajar": "{Sumber Belajar}",
                "lembar_kerja_peserta_didik": "{Lembar Kerja Peserta Didik}"
            },
            "tujuan_kegiatan_pembelajaran": {
                "tujuan_pembelajaran_topik": ["{Tujuan Pembelajaran Topik}"], // Berikan minimal 4 item.
                "tujuan_pembelajaran_pertemuan": ["{Tujuan Pembelajaran Pertemuan}"] // Tanpa menuliskan pertemuan ke berapa, jumlahnya menyesuaikan dengan alokasi_waktu informasi_umum. Misalkan alokasi_waktu 8 pertemuan maka ada 8 item tujuan_pembelajaran_pertemuan.
            },
            "pemahaman_bermakna": {
                "topik": "{Topik, berupa 1 paragraf}"
            },
            "pertanyaan_pemantik": ["", "", "", ""],
            "kompetensi_dasar": [
                {
                    "nama_kompetensi_dasar": "{Nama Kompetensi Dasar}",
                    "materi_pembelajaran": [
                        {
                            "materi": "{Materi}",
                            "tujuan_pembelajaran_materi": "{Tujuan Pembelajaran Materi}",
                            "indikator": "{Indikator}",
                            "nilai_karakter": "{Nilai Karakter}",
                            "kegiatan_pembelajaran": "{Kegiatan Pembelajaran}",
                            "alokasi_waktu": "{Alokasi Waktu, berupa berapa pertemuan}",
                            "penilaian": [
                                {
                                    "jenis": "",
                                    "bobot": 0
                                },
                                {
                                    "jenis": "",
                                    "bobot": 0
                                }
                            ]
                        },
                        {
                            "materi": "{Materi}",
                            "tujuan_pembelajaran_materi": "{Tujuan Pembelajaran Materi}",
                            "indikator": "{Indikator}",
                            "nilai_karakter": "{Nilai Karakter}",
                            "kegiatan_pembelajaran": "{Kegiatan Pembelajaran}",
                            "alokasi_waktu": "{Alokasi Waktu, berupa berapa pertemuan}",
                            "penilaian": [
                                {
                                    "jenis": "",
                                    "bobot": 0
                                },
                                {
                                    "jenis": "",
                                    "bobot": 0
                                }
                            ]
                        },
                        {
                            "materi": "{Materi}",
                            "tujuan_pembelajaran_materi": "{Tujuan Pembelajaran Materi}",
                            "indikator": "{Indikator}",
                            "nilai_karakter": "{Nilai Karakter}",
                            "kegiatan_pembelajaran": "{Kegiatan Pembelajaran}",
                            "alokasi_waktu": "{Alokasi Waktu, berupa berapa pertemuan}",
                            "penilaian": [
                                {
                                    "jenis": "",
                                    "bobot": 0
                                }
                            ]
                        }
                    ]
                },
                {
                    "nama_kompetensi_dasar": "{Nama Kompetensi Dasar}",
                    "materi_pembelajaran": [
                        {
                            "materi": "{Materi}",
                            "tujuan_pembelajaran_materi": "{Tujuan Pembelajaran Materi}",
                            "indikator": "{Indikator}",
                            "nilai_karakter": "{Nilai Karakter}",
                            "kegiatan_pembelajaran": "{Kegiatan Pembelajaran}",
                            "alokasi_waktu": "{Alokasi Waktu, berupa berapa pertemuan}",
                            "penilaian": [
                                {
                                    "jenis": "",
                                    "bobot": 0
                                },
                                {
                                    "jenis": "",
                                    "bobot": 0
                                }
                            ]
                        },
                        {
                            "materi": "{Materi}",
                            "tujuan_pembelajaran_materi": "{Tujuan Pembelajaran Materi}",
                            "indikator": "{Indikator}",
                            "nilai_karakter": "{Nilai Karakter}",
                            "kegiatan_pembelajaran": "{Kegiatan Pembelajaran}",
                            "alokasi_waktu": "{Alokasi Waktu, berupa berapa pertemuan}",
                            "penilaian": [
                                {
                                    "jenis": "",
                                    "bobot": 0
                                }
                            ]
                        }
                    ]
                }
            ],
            "lampiran": {
                "glosarium_materi": [
                    "{Glosarium Materi 1}",
                    "{Glosarium Materi 2}",
                    "{Glosarium Materi 3}",
                    "{Glosarium Materi 4}",
                    "{Glosarium Materi 5}",
                    "{Glosarium Materi 6}",
                    "{Glosarium Materi 7}",
                    "{Glosarium Materi 8}",
                    "{Glosarium Materi 9}",
                    "{Glosarium Materi 10}",
                ],
                "daftar_pustaka": [
                    "{Daftar Pustaka 1}",
                    "{Daftar Pustaka 2}",
                    "{Daftar Pustaka 3}",
                    "{Daftar Pustaka 4}",
                    "{Daftar Pustaka 5}"
                ]
            }
        }
        Pastikan mengisi semua field yang ada di atas dengan data dan format yang benar. Terima kasih atas kerja sama Anda.
        ';

        return $prompt;
    }
}

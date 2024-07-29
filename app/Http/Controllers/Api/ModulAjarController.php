<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ModulAjarHistories;
use App\Models\CapaianPembelajaran;
use App\Services\OpenAIService;
use icircle\Template\Docx\DocxTemplate;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ModulAjarController extends Controller
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
                'name'  =>'required',
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

            // Check if the user has less than 20 limit generate
            if ($user->generateAllSum() >= $user->limit_generate) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Anda telah mencapai batas maksimal untuk riwayat modul ajar.',
                    'data' => [
                        'generated_all_num' => $user->generateAllSum(),
                        'limit_all_num' => $user->limit_generate
                    ],
                ], 400);
            }

            // Parameters
            $namaModulAjar  = $request->input('name');
            $faseKelas      = $request->input('phase');
            $mataPelajaran  = $request->input('subject');
            $elemen         = $request->input('element');
            $addNotes       = $request->input('notes');

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

            $prompt         = $this->prompt($faseKelas, $mataPelajaran, $elemen, $capaianPembelajaran, $addNotes);

            // Send the message to OpenAI
            $resMessage = $this->openAI->sendMessage($prompt);

            $parsedResponse = json_decode($resMessage, true);
            $user = $request->user();

            $faseToKelas = [
                'Fase A' => 'Kelas 1 - 2 SD',
                'Fase B' => 'Kelas 3 - 4 SD',
                'Fase C' => 'Kelas 5 - 6 SD',
                'Fase D' => 'Kelas 7 - 9 SMP',
                'Fase E' => 'Kelas 10 SMA',
                'Fase F' => 'Kelas 11 - 12 SMA'
            ];

            $kelas = isset($faseToKelas[$faseKelas]) ? "{$faseKelas} ({$faseToKelas[$faseKelas]})" : $faseKelas;

            $parsedResponse['informasi_umum']['nama_modul_ajar']        = $namaModulAjar;
            $parsedResponse['informasi_umum']['penyusun']               = $user->name;
            $parsedResponse['informasi_umum']['jenjang_sekolah']        = $user->school_name;
            $parsedResponse['informasi_umum']['fase_kelas']             = $kelas;
            $parsedResponse['informasi_umum']['mata_pelajaran']         = $mataPelajaran;
            $parsedResponse['informasi_umum']['capaian_pembelajaran']   = $capaianPembelajaran;
            $parsedResponse['informasi_umum']['tahun_penyusunan']       = Date('Y');

            // Construct the response data for success
            $insertData = ModulAjarHistories::create([
                'name' => $namaModulAjar,
                'phase' => $faseKelas,
                'subject' => $mataPelajaran,
                'element' => $elemen,
                'notes' => $addNotes,
                'output_data' => $parsedResponse,
                'user_id' => auth()->id(),
            ]);

            $parsedResponse['id'] = $insertData->id;

            // return new APIResponse($responseData);
            return response()->json([
                'status' => 'success',
                'message' => 'Modul ajar berhasil dihasilkan',
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
            $templatePath   = public_path('word_template/Modul_Ajar_V2_Template.docx');
            $docxTemplate   = new DocxTemplate($templatePath);
            $outputPath     = public_path('word_output/Modul_Ajar_' . auth()->id() . '_' . md5(time() . '' . rand(1000, 9999)) . '.docx');

            $modulAjarId  = $request->input('id');
            $modulAjar    = ModulAjarHistories::find($modulAjarId);

            $data = $modulAjar->output_data;
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
            $modulAjarId = $request->input('id');
            if (!is_numeric($modulAjarId)) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'ID tidak valid.',
                ], 400);
            }

            $user = $request->user();
            $modulAjar = $user->modulAjarHistory()->find($modulAjarId);

            if (!$hintHistory) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Riwayat modul ajar tidak tersedia pada akun ini!',
                    'data' => null,
                ], 404);
            }

            $data = $modulAjar->output_data;

            // Path template Excel
            $templatePath = public_path('excel_template/Modul_Ajar_Template.xlsx');

            // Load template Excel
            $spreadsheet = IOFactory::load($templatePath);
            $sheet = $spreadsheet->getActiveSheet();

            // Mengisi data ke dalam template sesuai dengan format yang diberikan
            $sheet->setTitle($data['informasi_umum']['nama_kisi_kisi']);
            $sheet->setCellValue('E3', $data['informasi_umum']['tahun_penyusunan']);
            $sheet->setCellValue('C6', $data['informasi_umum']['instansi']);
            $sheet->setCellValue('C7', $data['informasi_umum']['mata_pelajaran']);
            $sheet->setCellValue('C8', $data['informasi_umum']['kelas']);
            $sheet->setCellValue('F7', $data['informasi_umum']['jumlah_soal']);
            $sheet->setCellValue('F9', $data['informasi_umum']['penyusun']);
            $sheet->setCellValue('C13', $data['informasi_umum']['capaian_pembelajaran_redaksi']);
            $sheet->setCellValue('C14', $data['informasi_umum']['elemen_capaian']);
            $sheet->setCellValue('C15', $data['informasi_umum']['pokok_materi']);

            // Mengisi data kisi-kisi
            $templateRow = 17;
            $rowCount = count($data['kisi_kisi']);
            $highestRow = $templateRow + $rowCount - 1;

            for ($row = $templateRow; $row <= $highestRow; $row++) {
                if ($row != $templateRow) {
                    $sheet->duplicateStyle($sheet->getStyle('B' . $templateRow . ':F' . $templateRow), 'B' . $row . ':F' . $row);
                }

                $sheet->mergeCells("C{$row}:E{$row}");

                $sheet->setCellValue("B{$row}", $data['kisi_kisi'][$row - $templateRow]['nomor']);
                $sheet->setCellValue("C{$row}", $data['kisi_kisi'][$row - $templateRow]['indikator_soal']);
                $sheet->setCellValue("F{$row}", $data['kisi_kisi'][$row - $templateRow]['no_soal']);

                // Mengatur tinggi baris dan wrapping text
                $sheet->getRowDimension($row)->setRowHeight(-1);
                $sheet->getStyle("B{$row}:F{$row}")->getAlignment()->setWrapText(true);
                $sheet->getStyle("C{$row}:E{$row}")->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_LEFT);
            }

            // Menyimpan spreadsheet ke file baru
            $fileName = 'Modul_Ajar_' . auth()->id() . '_' . md5(time() . '' . rand(1000, 9999)) . '.xlsx';
            $filePath = public_path('excel_output/' . $fileName);

            $writer = new Xlsx($spreadsheet);
            $writer->save($filePath);

            return response()->json([
                'status' => 'success',
                'message' => 'Dokumen Excel berhasil dibuat',
                'data' => ['output_path' => $filePath, 'download_url' => url('excel_output/' . $fileName)],
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
            $modulAjarHistories = $user->modulAjarHistory()
                ->select(['id', 'name', 'phase', 'subject', 'element', 'notes', 'user_id', 'created_at', 'updated_at'])
                ->get();

            if ($modulAjarHistories->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak ada riwayat modul ajar untuk akun anda!',
                    'data' => [
                        'generated_num' => 0,
                        'items' => [],
                    ],
                ], 200);
            }

            // Calculate the total generated hints by the user
            $generatedNum = $modulAjarHistories->count();

            // Return the response with hint histories data
            return response()->json([
                'status' => 'success',
                'message' => 'Riwayat modul ajar berhasil ditampilkan!',
                'data' => [
                    'generated_num' => $generatedNum,
                    'items' => $modulAjarHistories,
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
            $modulAjarHistories = $user->modulAjarHistory()->find($id);

            if (!$modulAjarHistories) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Riwayat hasil modul ajar tidak tersedia pada akun ini!',
                    'data' => null,
                ], 404);
            }

            // Return the response with hint history data
            return response()->json([
                'status' => 'success',
                'message' => 'Modul ajar history retrieved successfully',
                'data' => $modulAjarHistories,
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
        - elemen: ' . $elemen . '
        - capaian_pembelajaran: ' . $capaianPembelajaran . '

        Buatlah Modul ajar dimana array komponen_pembelajaran, tujuan_kegiatan_pembelajaran, pemahaman_bermakna, pertanyaan_pemantik, dan kompetensi_dasar isinya akan berdasarkan mata_pelajaran, capaian_pembelajaran, serta elemen. Catatan tambahan dalam bahasa Indonesia: ' . $addNotes . '

        Modul Ajar merupakan materi pembelajaran terstruktur yang digunakan sebagai alat bantu guru dalam proses pengajaran dan proses pembelajaran siswa. Modul Ajar dirancang sedemikian rupa agar dapat mencapai target Capaian Pembelajaran (CP).

        Secara struktur, komponen dari Modul Ajar adalah sebagai berikut:
        - alokasi_waktu : Alokasi waktu pada bagian informasi_umum merupakan waktu yang dibutuhkan untuk menyelesaikan seluruh Modul Ajar seperti materi dan aktivitas pembelajaran berupa berapa kali pertemuan yang dibutuhkan untuk menyelesaikannya.
        - kompetensi_dasar : Array yang berisikan rincian materi dalam Modul ajar.
               - nama_kompetensi_dasar : Bagian dari array kompetensi_dasar yang berisi nama materi pembelajaran dengan acuan dari mata_pelajaran, elemen, dan capaian_pembelajaran.
               - materi_pembelajaran : Bagian dari array kompetensi_dasar yang berisi materi pembelajaran yang dibutuhkan untuk menyelesaikan kompetensi dasar.
                         - materi : Bagian dari array materi_pembelajaran yang merupakan nama spesifik dari nama_kompetensi_dasar.
                         - tujuan_pembelajaran_materi : Tujuan yang menjadi acuan peserta didik dianggap telah memahami materi pembelajaran.
                         - indikator : Hasil akhir dari tujuan_pembelajaran_materi.
                         - alokasi_waktu : Mengambil jatah alokasi_waktu pada informasi_umum. Total alokasi_waktu pada array materi_pembelajaran harus sesuai dengan alokasi_waktu pada informasi_umum.
        - glosarium_materi : Memiliki 7 item yang diurutkan secara alfabet. Setiap item pada Glosarium Materi harus berkaitan dengan mata_pelajaran, capaian_pembelajaran, serta elemen. Satu item pada Glosarium Materi berupa 1 kata diikuti dengan definisinya. Misalkan "Air: senyawa tak berwarna, tak berbau,...".
        - daftar_pustaka : Memiliki 5 item yang diurutkan secara alfabet. Daftar pustaka merupakan referensi yang digunakan untuk materi pada Modul Ajar. Setiap item pada Daftar Pustaka harus lengkap sesuai tata cara penulisan "petajukobit" yaitu penulis, tahun, judul, kota, penerbit. Pastikan setiap item pada Daftar Pustaka adalah referensi nyata bukan fiktif!

        Sertakan bidang berikut untuk setiap bagian dari Modul Ajar sebagai berikut:
        - kompetensi_awal : Persyaratan yang perlu dikuasai peserta didik sebelum mengikuti pembelajaran.
        - profil_pelajar_pancasila : Jabarkan secara singkat sikap yang diperlukan oleh peserta didik sesuai dengan nilai-nilai yang terkandung dalam Pancasila dan berkaitan dengan elemen.
        - target_peserta_didik : Tujuan yang dicapai oleh peserta didik setelah mengikuti pembelajaran.
        - model_pembelajaran : Metode yang digunakan untuk menyampaikan materi pembelajaran. Misalkan menggunakan tugas proyek, pendekatan tugas, dan sejenisnya.
        - sumber_belajar : Sumber materi yang digunakan dalam pembelajaran. Berikan dalam bentuk paragraf.
        - lembar_kerja_peserta_didik : Media yang digunakan peserta didik untuk mengerjakan materi pembelajaran seperti buku catatan, lembar kerja siswa, dan sejenisnya. Berikan dalam bentuk paragraf.

        Array "tujuan_kegiatan_pembelajaran" sebagai berikut:
        - tujuan_pembelajaran_pertemuan : Tujuan pembelajaran pada setiap pertemuan tanpa menuliskan pertemuan ke berapa. Data untuk tujuan_pembelajaran_pertemuan menyesuaikan jumlah pertemuan dari "alokasi_waktu".
        - tujuan_pembelajaran_topik : Hasil yang diharapkan dapat dicapai oleh peserta didik setelah mengikuti pembelajaran setiap pertemuan.

        {
            "informasi_umum": {
                "alokasi_waktu": "{Alokasi Waktu}",
                "kompetensi_awal": "{Kompetensi Awal}",
                "profil_pelajar_pancasila": "{Profil Pelajar Pancasila}",
                "target_peserta_didik": "{Target Peserta Didik}",
                "model_pembelajaran": "{Model Pembelajaran}"
            },
            "sarana_dan_prasarana": {
                "sumber_belajar": "{Sumber Belajar}",
                "lembar_kerja_peserta_didik": "{Lembar Kerja Peserta Didik}"
            },
            "tujuan_kegiatan_pembelajaran": {
                "tujuan_pembelajaran_bab": "{Tujuan Pembelajaran Bab}",
                "tujuan_pembelajaran_topik": ["{Tujuan Pembelajaran Topik}"], // Berikan minimal 4 item.
                "tujuan_pembelajaran_pertemuan": ["{Tujuan Pembelajaran Pertemuan}"] // Tanpa menuliskan pertemuan ke berapa, jumlahnya menyesuaikan dengan alokasi_waktu informasi_umum. Misalkan alokasi_waktu 8 pertemuan maka ada 8 item tujuan_pembelajaran_pertemuan.
            },
            "pemahaman_bermakna": {
                "topik": "{Topik, berupa 1 paragraf}"
            },
            "pertanyaan_pemantik": ["", "", "", ""],
            "kompetensi_dasar": [
                {
                    "nama_kompetensi_dasar": "",
                    "materi_pembelajaran": [
                        {
                            "materi": "{Materi}",
                            "tujuan_pembelajaran_materi": "{Tujuan Pembelajaran Materi}",
                            "indikator": "",
                            "nilai_karakter": "",
                            "kegiatan_pembelajaran": "",
                            "alokasi_waktu": "",
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
                            "indikator": "",
                            "nilai_karakter": "",
                            "kegiatan_pembelajaran": "",
                            "alokasi_waktu": "",
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
                    "nama_kompetensi_dasar": "",
                    "materi_pembelajaran": [
                        {
                            "materi": "{Materi}",
                            "tujuan_pembelajaran_materi": "{Tujuan Pembelajaran Materi}",
                            "indikator": "",
                            "nilai_karakter": "",
                            "kegiatan_pembelajaran": "",
                            "alokasi_waktu": "",
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
                            "indikator": "",
                            "nilai_karakter": "",
                            "kegiatan_pembelajaran": "",
                            "alokasi_waktu": "",
                            "penilaian": [
                                {
                                    "jenis": "",
                                    "bobot": 0
                                }
                            ]
                        }
                    ]
                }
            ], // Pastikan alokasi_waktu pada kompetensi_dasar sesuai dengan alokasi_waktu pada informasi_umum.
            "lampiran": {
                "glosarium_materi": [
                    "{Glosarium Materi 1}",
                    "{Glosarium Materi 2}",
                    "{Glosarium Materi 3}",
                    "{Glosarium Materi 4}",
                    "{Glosarium Materi 5}",
                    "{Glosarium Materi 6}",
                    "{Glosarium Materi 7}"
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
        ';

        return $prompt;
    }

}

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

    public function prompt($faseKelas, $mataPelajaran, $elemen, $capaianPembelajaran, $addNotes)
    {
        $prompt = 'Buatlah bahan ajar untuk mata pelajaran ' . $mataPelajaran . ' pada tingkat kelas ' . $faseKelas . ' dengan memperhatikan ' . $elemen . ' dan catatan khusus berikut: ' . $addNotes . '.

        Jelaskan identitas modul, kompetensi awal, profil pelajar terkait Pancasila (jika ada), serta sarana dan prasarana yang diperlukan. Tentukan juga target peserta didik dan model pembelajaran yang sesuai.

        Selanjutnya, rinci tujuan pembelajaran, pemahaman bermakna, dan pertanyaan pemantik yang relevan untuk mencapai kompetensi yang ditetapkan. Terakhir, susun kegiatan pembelajaran dengan mencantumkan 4 objek kompetensi dasar. Setiap objek kompetensi dasar harus memiliki informasi tentang materi pembelajaran, indikator pencapaian, nilai karakter yang ingin ditanamkan, alokasi waktu, dan jenis penilaian beserta bobotnya.

        Pastikan setiap bagian memiliki informasi yang cukup dan relevan untuk membantu pendidik atau pembelajar memahami dan melaksanakan materi pembelajaran dengan efektif.

        Berikan saya output dengan format JSON seperti ini:

        {
            "informasi_umum": {
                "penyusun": "",
                "jenjang_sekolah": "",
                "tahun_penyusunan": "",
                "mata_pelajaran": "",
                "fase_kelas": "",
                "alokasi_waktu": "", Perhatian: Untuk satu kali pertemuan alokasi waktunya 2 jam, silahkan pikirkan berapa pertemuan, maksimal 4 pertemuan untuk 1 bahan ajar
                "kompetensi_awal": "(Berbentuk 1 Paragraf/Alinea)",
                "profil_pelajar_pancasila": "(Berbentuk 1 Paragraf/Alinea)", Perhatian: Pastikan profil pelajar sesuai dengan mata pelajaran yang dipilih, jangan ada unsur PPKN, dan harus ada profil pelajar yang mencerminkan pancasila.
                "target_peserta_didik": "(Berbentuk 1 Paragraf/Alinea)",
                "model_pembelajaran": "(Berbentuk 1 Paragraf/Alinea)",
                "capaian_pembelajaran": ""
            },
            "sarana_dan_prasarana": {
                "sumber_belajar": "(Berbentuk 1 Paragraf/Alinea)",
                "lembar_kerja_peserta_didik": "(Berbentuk 1 Paragraf/Alinea)"
            },
            "komponen_pembelajaran": {
                "perlengkapan_peserta_didik": ["", "", "", ""],
                "perlengkapan_guru": ["", "", "", ""
                ]
            },
            "tujuan_kegiatan_pembelajaran": {
                "tujuan_pembelajaran_bab": "(Berbentuk 1 Paragraf/Alinea)",
                "tujuan_pembelajaran_topik": ["", "", "", ""]
                "tujuan_pembelajaran_pertemuan": ["", "", "", "", "", "", "", ""], "(Berbentuk 1 Paragraf/Alinea untuk setiap pertemuan tanpa menuliskan pertemuan ke berapa, ambil data "alokasi_waktu" di atas untuk menentukan berapa kali pertemuan)",
            },
            "pemahaman_bermakna": {
                "topik": "(Berbentuk 1 Paragraf/Alinea)"
            },
            "pertanyaan_pemantik": ["", "", "", ""],
            "kompetensi_dasar": [
                {
                    "nama_kompetensi_dasar": "", //nama
                    "materi_pembelajaran": [
                        {
                            "materi": "",
                            "tujuan_pembelajaran_materi": "(Berbentuk 1 Paragraf/Alinea)",
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
                            "materi": "",
                            "tujuan_pembelajaran_materi": "(Berbentuk 1 Paragraf/Alinea)",
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
                            "materi": "",
                            "tujuan_pembelajaran_materi": "(Berbentuk 1 Paragraf/Alinea)",
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
                            "materi": "",
                            "tujuan_pembelajaran_materi": "(Berbentuk 1 Paragraf/Alinea)",
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
                            "materi": "",
                            "tujuan_pembelajaran_materi": "(Berbentuk 1 Paragraf/Alinea)",
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
                            "materi": "",
                            "tujuan_pembelajaran_materi": "(Berbentuk 1 Paragraf/Alinea)",
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
            ]
            "lampiran": {
                "glorasium_materi": ["", "", "", "", "", "", "", "", "", ""] //Berikan 10 item glorasium masing masing 1 item 1 kalimat penjelasan aftar alfabetis istilah dalam suatu ranah pengetahuan tertentu yang dilengkapi dengan definisi untuk istilah-istilah tersebut, dan harus ada nyata, jangan hanya contoh dan penjelasanya juga harus ada.
                "daftar_pustaka": ["", "", "", "", "", "", "", "", "", ""] //Perhatian: Mohon berikan 10 daftar pustaka yang relevan dengan materi seperti mengutip dari jurnal ilmiah, artikel ilmiah, buku pelajaran, jangan berupa data fiktif! Pastikan daftar pustaka menggunakan format referensi yang sesuai.
            }
        }

        ';

        return $prompt;
    }
}

<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\MailHistories;
use App\Models\CreditLog;
use App\Models\ModuleCreditCharge;
use App\Services\OpenAIService;
use icircle\Template\Docx\DocxTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\RichText\RichText;
use PhpOffice\PhpSpreadsheet\RichText\Run;

class MailController extends Controller
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
                'nama_surat'  => 'required',
                'jenis_surat' => 'required',
                'tujuan_surat' => 'required',
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
            $moduleCredit = ModuleCreditCharge::where('slug', 'mail')->first();
            $creditCharge = $moduleCredit->credit_charged_generate ?? 1;
            if ($user->credit < $creditCharge) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Anda telah mencapai batas maksimal untuk riwayat persuratan.',
                    'data' => [
                        'user_credit' => $user->credit,
                        'generated_all_num' => $user->generateAllSum(),
                        'limit_all_num' => $user->limit_generate
                    ],
                ], 400);
            }

            // Parameters
            $namaSurat  = $request->input('name_surat');
            $jenisSurat        = $request->input('jenis_surat');
            $tujuanSurat  = $request->input('tujuan_surat');
            $addNotes       = $request->input('notes');

            // Send the message to OpenAI
            $prompt = $this->prompt($namaSurat, $jenisSurat, $tujuanSurat, $addNotes);
            $resMessage = $this->openAI->sendMessage($prompt);
            $parsedResponse = json_decode($resMessage, true);

            // Add additional information
            $parsedResponse['informasi_umum']['nama_surat'] = $namaSurat;
            $parsedResponse['informasi_umum']['jenis_surat'] = $jenisSurat;
            $parsedResponse['informasi_umum']['tujuan_surat'] = $tujuanSurat;
            $parsedResponse['informasi_umum']['nama_sekolah'] = $user->school_name;
            $parsedResponse['informasi_umum']['tanggal'] = Date('D','M','Y');

            // Insert data into ModulAjarHistories
            $insertData = MailHistories::create([
                'nama_surat' => $namaSurat,
                'jenis_surat' => $jenisSurat,
                'tujuan_surat' => $tujuanSurat,
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
                'description' => 'Generate Persuratan',
            ]);

            // Add ID to response
            $parsedResponse['id'] = $insertData->id;

            return response()->json([
                'status' => 'success',
                'message' => 'Persuratan berhasil dihasilkan',
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
            $templatePath   = public_path('word_template/Persuratan_Template.docx');
            $docxTemplate   = new DocxTemplate($templatePath);
            $outputPath     = public_path('word_output/Persuratan_' . auth()->id() . '_' . md5(time() . '' . rand(1000, 9999)) . '.docx');

            $mailId  = $request->input('id');
            $mail    = MailHistories::find($mailId);

            $data = $mail->generate_output;
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
            $mailHistories = $user->mailHistory()
                ->select(['id', 'nama_surat', 'jenis_surat', 'tujuan_surat', 'notes', 'user_id', 'created_at', 'updated_at'])
                ->get();

            if ($mailHistories->isEmpty()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Tidak ada riwayat persuratan untuk akun anda!',
                    'data' => [
                        'generated_num' => 0,
                        'items' => [],
                    ],
                ], 200);
            }

            // Calculate the total generated hints by the user
            $generatedNum = $mailHistories->count();

            // Return the response with hint histories data
            return response()->json([
                'status' => 'success',
                'message' => 'Riwayat persuratan berhasil ditampilkan!',
                'data' => [
                    'generated_num' => $generatedNum,
                    'items' => $mailHistories,
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
            $mailHistories = $user->mailHistory()->find($id);

            if (!$mailHistories) {
                return response()->json([
                    'status' => 'failed',
                    'message' => 'Riwayat hasil persuratan tidak tersedia pada akun ini!',
                    'data' => null,
                ], 404);
            }

            // Return the response with hint history data
            return response()->json([
                'status' => 'success',
                'message' => 'Riwayat hasil persuratan berhasil ditampilkan!',
                'data' => $mailHistories,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'failed',
                'message' => $e->getMessage(),
                'data' => json_decode($e->getMessage(), true),
            ], 500);
        }
    }

    public function prompt($namaSurat, $jenisSurat, $tujuanSurat, $addNotes)
    {
        $prompt = '';
        $prompt .= '
        Buatlah objek JSON untuk Persuratan berdasarkan parameter berikut:

        - nama_surat: ' . $namaSurat . '
        - jenis_surat: ' . $jenisSurat . '
        - tujuan_surat: ' . $tujuanSurat . '
        - notes: ' . $addNotes . '

        nomor_surat: Berkaitan dengan nomor surat dengan format kode klasifikasi/nomor surat/tahun.
        hal: Perihal surat yang berkaitan dengan jenis_surat.
        elemen_gamifikasi: Gamification elements consisting of titles and descriptions.
        misi_dan_tantangan: Missions and challenges with types, descriptions, and points.
        langkah_implementasi: Create the `langkah_implementasi` section which will be displayed as instructions for students, including:
            step: Numbered steps for implementation.
            title: Brief titles for each step.
            description: Detailed instructions for each step presented as an array. Minimum 2 points.

        Game Scheme Explanation:
        - For individual game scheme: Each student competes individually, earning points and achievements based on their own efforts.
        - For group game scheme: Students collaborate in teams to complete missions and challenges, fostering teamwork and collective achievement.

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

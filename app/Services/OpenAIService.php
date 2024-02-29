<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class OpenAIService
{
    private $authorization;
    private $endpoint;
    private $httpClient;

    public function __construct()
    {
        $this->authorization = env('OPEN_AI_KEY');
        $this->endpoint = 'https://api.openai.com/v1/chat/completions';

        $this->httpClient = new Client([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->authorization,
            ],
        ]);
    }

    public function sendMessage($message)
    {
        try {
            $data = [
                'messages' => [
                    [
                        'role' => 'user',
                        'content' => $message,
                    ],
                ],
                'model' => 'gpt-3.5-turbo-1106',
                'response_format' => ['type' => 'json_object']
            ];

            $response = $this->httpClient->post($this->endpoint, [
                'json' => $data,
            ]);

            if ($response->getStatusCode() === 200) {
                $arrResult = json_decode($response->getBody(), true);
                $resultMessage = $arrResult["choices"][0]["message"]["content"];
                return $resultMessage;
            } else {
                throw new \Exception('Error: Unexpected HTTP status code - ' . $response->getStatusCode());
            }
        } catch (RequestException $e) {
            $message = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();
            $message = json_decode($message, true);
            throw new \Exception('Error sending the message : ' . $message['error']['message']);
        }
    }

    public function generateMaterialsPromptBeta($subject, $grade, $notes)
    {
        $prompt = 'Buatlah bahan ajar untuk mata pelajaran ' . $subject . ' pada tingkat kelas ' . $grade . ' dengan memperhatikan catatan khusus berikut: ' . $notes . '.
        
        Jelaskan identitas modul, kompetensi awal, profil pelajar terkait Pancasila (jika ada), serta sarana dan prasarana yang diperlukan. Tentukan juga target peserta didik dan model pembelajaran yang sesuai.

        Selanjutnya, rinci tujuan pembelajaran, pemahaman bermakna, dan pertanyaan pemantik yang relevan untuk mencapai kompetensi yang ditetapkan. Terakhir, susun kegiatan pembelajaran dengan mencantumkan 4 objek kompetensi dasar. Setiap objek kompetensi dasar harus memiliki informasi tentang materi pembelajaran, indikator pencapaian, nilai karakter yang ingin ditanamkan, alokasi waktu, dan jenis penilaian beserta bobotnya.
        
        Pastikan setiap bagian memiliki informasi yang cukup dan relevan untuk membantu pendidik atau pembelajar memahami dan melaksanakan materi pembelajaran dengan efektif.
        
        Berikan saya output dengan format JSON seperti ini:
            
        {
            "informasi_umum": {
                "penyusun": "",
                "instansi": "",
                "tahun_penyusunan": "",
                "jenjang_sekolah": "",
                "mata_pelajaran": "",
                "fase_kelas": "",
                "topik": "(Berbentuk 1 Paragraf/Alinea)",
                "alokasi_waktu": "", Perhatian: Untuk satu kali pertemuan alokasi waktunya 2 jam, silahkan pikirkan berapa pertemuan, maksimal 4 pertemuan untuk 1 bahan ajar
                "kompetensi_awal": "(Berbentuk 1 Paragraf/Alinea)",
                "profil_pelajar_pancasila": "(Berbentuk 1 Paragraf/Alinea)", Perhatian: Pastikan profil pelajar sesuai dengan mata pelajaran yang dipilih, jangan ada unsur PPKN, dan harus ada profil pelajar yang mencerminkan pancasila.
                "target_peserta_didik": "(Berbentuk 1 Paragraf/Alinea)",
                "model_pembelajaran": "(Berbentuk 1 Paragraf/Alinea)"
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
                "daftar_pustaka": ["", "", "", "", "", "", "", "", "", ""] //Berikan 10 item daftar pustaka masing masing 1 Daftar pustaka adalah daftar sumber yang digunakan untuk mengutip publikasi ilmiah, dan harus ada nyata, jangan hanya contoh tidak ada data!.
            }
        }
            
        ';

        return $prompt;
    }

    public function generateExercisesEssayPrompt($subject, $grade, $number_of_question, $notes)
    {
        // Construct the prompt
        $prompt = "Generate latihan untuk mata pelajaran: {$subject}, tingkat kelas: {$grade} dengan memperhatikan catatan khusus berikut: {$notes}" . PHP_EOL .
            "Perhatian: Mohon jawab dengan format JSON berikut:" . PHP_EOL .
            '{
                "informasi_umum": {
                    "penyusun": "",
                    "instansi": "",
                    "tahun_penyusunan": "",
                    "jenjang_sekolah": "",
                    "mata_pelajaran": "",
                    "fase_kelas": "",
                    "topik": "(Berbentuk 1 Paragraf/Alinea)",
                    "alokasi_waktu": "",
                    "kompetensi_awal": "(Berbentuk 1 Paragraf/Alinea)"
                },
                "soal_essay": [
                    {
                        "question": "",
                        "instructions": ""
                    }
                ]
            }' . PHP_EOL .
            "Jumlah soal yang diminta: {$number_of_question}";

        // Return the generated prompt
        return $prompt;
    }
}

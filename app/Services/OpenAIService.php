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

    public function generateMaterialsPrompt($subject, $grade, $notes)
    {
        $prompt = 'Buatlah bahan ajar untuk mata pelajaran ' . $subject . ' pada tingkat kelas ' . $grade . ' dengan memperhatikan catatan khusus berikut: ' . $notes . '.

        Jelaskan identitas modul, kompetensi awal, profil pelajar terkait Pancasila (jika ada), serta sarana dan prasarana yang diperlukan. Tentukan juga target peserta didik dan model pembelajaran yang sesuai.

        Selanjutnya, rinci tujuan pembelajaran, pemahaman bermakna, dan pertanyaan pemantik yang relevan untuk mencapai kompetensi yang ditetapkan. Terakhir, susun kegiatan pembelajaran dengan mencantumkan 4 objek kompetensi dasar. Setiap objek kompetensi dasar harus memiliki informasi tentang materi pembelajaran, indikator pencapaian, nilai karakter yang ingin ditanamkan, alokasi waktu, dan jenis penilaian beserta bobotnya.

        Pastikan setiap bagian memiliki informasi yang cukup dan relevan untuk membantu pendidik atau pembelajar memahami dan melaksanakan materi pembelajaran dengan efektif.

        Berikan saya output dengan format JSON seperti ini:

            {
                "informasi_umum": {
                    "identitas_modul": {
                        "nama_penyusun": "Nama Penyusun",
                        "satuan_pendidikan": "Satuan Pendidikan",
                        "fase_kelas": "Fase / Kelas",
                        "mata_pelajaran": "Mata Pelajaran",
                        "alokasi_waktu": "Alokasi Waktu"
                    },
                    "kompetensi_awal": "Kompetensi Awal (Berbentuk 1 Paragraf/Alinea)",
                    "profil_pelajar_pancasila": [],
                    "sarana_dan_prasarana": {
                        "sumber_belajar": "",
                        "lembar_kerja_peserta_didik": ""
                    },
                    "target_peserta_didik": "Target Peserta Didik (Berbentuk 1 Paragraf/Alinea)",
                    "model_pembelajaran": "Model Pembelajaran (Berbentuk 1 Paragraf/Alinea)"
                },
                "komponen_inti": {
                    "tujuan_pembelajaran": [
                        "Berbentuk Array dan buat sejumlah 3 item"
                    ],
                    "pemahaman_bermakna": [
                        "Berbentuk Array dan buat sejumlah banyaknya kegiatan_pembelajaran item"
                    ],
                    "pertanyaan_pemantik": [
                        "Berbentuk Array dan buat sejumlah banyaknya kegiatan_pembelajaran item"
                    ],
                    "kegiatan_pembelajaran": [
                        {
                            "nama_kompetensi_dasar": "",
                            "materi_pembelajaran": [
                                {
                                    "materi": "",
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
                                },
                                {
                                    "materi": "",
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
                                },
                                {
                                    "materi": "",
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
                                },
                                {
                                    "materi": "",
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
                    ]
                }
            }

        ';

        return $prompt;
    }

    public function generateSyllabusPromptBeta($subject, $grade, $nip, $notes)
    {
        $prompt = 'Buatkan silabus untuk mata pelajaran ' . $subject . ' pada tingkat ' . $grade . ' dengan NIP/NIK ' . $nip . ' dengan memperhatikan catatan khusus berikut: ' . $notes . '.

        Jelaskan alokasi waktu, kompetensi inti (KI), kompetensi dasar, materi pembelajaran, dan kegiatan pembelajaran yang relevan.

        Selanjutnya rinci kompetensi inti (KI) menjadi empat bagian yaitu KI-1 yang menyangkut spiritual, KI-2 yang menyangkut sosial, KI-3 yang menyangkut pengetahuan, dan KI-4 yang menyangkut keterampilan.

        Pastikan setiap bagian memiliki informasi yang cukup dan relevan untuk membantu pendidik atau pembelajar memahami dan melaksanakan materi pembelajaran dengan efektif.

        Berikan saya output dengan format JSON seperti ini:

        {
            "informasi_umum": {
                "mata_pelajaran" : "",
                "nama_sekolah" : "",
                "tingkat_kelas": "",
                "nip" : "",
            },
            "silabus_pembelajaran": {
                "mata_pelajaran" : "",
                "tingkat_kelas" : "",
                "alokasi_waktu" : "", Perhatian: Alokasi waktu berupa berapa jam pelajaran perminggunya
                "kompetensi_inti": [
                    "KI-1 (Spiritual) : "" ",
                    "KI-2 (Sosial) : "" ",
                    "KI-3 (Pengetahuan) : "" ",
                    "KI-4 (Keterampilan) : "" ",
                ],
                "definisi_kompetensi_inti" : "", Perhatian: Definisi kompetensi inti adalah pencapaian atau tujuan dari keempat kompetensi inti tersebut (Berbentuk 1 Paragraf/Alinea)
                "inti_silabus" : [
                    {
                        "kompetensi_dasar" : ["", ""],
                        "materi_pembelajaran" : ["", "", "", ""],
                        "kegiatan_pembelajaran" : ["", "", "", ""],
                    },
                    {
                        "kompetensi_dasar" : ["", ""],
                        "materi_pembelajaran" : ["", "", "", ""],
                        "kegiatan_pembelajaran" : ["", "", "", ""],
                    },
                    {
                        "kompetensi_dasar" : ["", ""],
                        "materi_pembelajaran" : ["", "", "", ""],
                        "kegiatan_pembelajaran" : ["", "", "", ""],
                    },
                    {
                        "kompetensi_dasar" : ["", ""],
                        "materi_pembelajaran" : ["", "", "", ""],
                        "kegiatan_pembelajaran" : ["", "", "", ""],
                    },
                    {
                        "kompetensi_dasar" : ["", ""],
                        "materi_pembelajaran" : ["", "", "", ""],
                        "kegiatan_pembelajaran" : ["", "", "", ""],
                    },
                    {
                        "kompetensi_dasar" : ["", ""],
                        "materi_pembelajaran" : ["", "", "", ""],
                        "kegiatan_pembelajaran" : ["", "", "", ""],
                    },
                ], Perhatikan: Pastikan setiap bagian pada item "inti_silabus" memiliki informasi yang lengkap dan relevan
            }
        }';

        return $prompt;
    }

    public function generateSyllabusPrompt($mataPelajaran, $tingkatKelas, $addNotes)
    {
        $prompt = '
        Buat kurikulum/silabus pendidikan untuk mata pelajaran ' . $mataPelajaran . ' pada kelas ' . $tingkatKelas . ' dengan rincian sebagai berikut:

            a. Sertakan lima Kompetensi Dasar, dan setiap Kompetensi Dasar harus memiliki dua Materi Pembelajaran.
            b. Jelaskan Indikator pencapaian untuk setiap Materi Pembelajaran.
            c. Tetapkan Nilai Karakter yang ingin ditanamkan untuk setiap Kompetensi Dasar.
            d. Gambarkan Kegiatan Pembelajaran yang sesuai dengan Materi Pembelajaran.
            e. Tentukan Alokasi Waktu yang disarankan untuk setiap Kompetensi Dasar.
            f. Jelaskan metode Penilaian yang akan digunakan.

            Notes Tambahan: ' . $addNotes . '

                Silakan tuliskan silabus dalam format JSON berikut :

                {
                    "informasi_umum": {
                        "penyusun": "",
                        "instansi": "",
                        "tahun_penyusunan": "",
                        "jenjang_sekolah": "",
                        "mata_pelajaran": "",
                        "fase_kelas": "",
                        "topik": "",
                        "alokasi_waktu": "",
                        "kompetensi_awal": ""
                    },
                    "sarana_dan_prasarana": {
                        "sumber_belajar": "",
                        "lembar_kerja_peserta_didik": ""
                    },
                    "komponen_pembelajaran": {
                        "perlengkapan_peserta_didik": [
                            "",
                            "",
                            "",
                            "",
                            ""
                        ],
                        "perlengkapan_guru": [
                            "",
                            "",
                            "",
                            ""
                        ]
                    },
                    "tujuan_kegiatan_pembelajaran": {
                        "tujuan_pembelajaran_bab": "",
                        "tujuan_pembelajaran_topik": [
                            "",
                            ""
                        ]
                    },
                    "pemahaman_bermakna": {
                        "topik": ""
                    },
                    "pertanyaan_pemantik": [
                        "",
                        ""
                    ],
                    "kompetensi_dasar": [
                        {
                            "nama_kompetensi_dasar": "",
                            "materi_pembelajaran": [
                                {
                                    "materi": "",
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
                }

            Pastikan untuk mengisi setiap item dengan rincian yang relevan dan sesuai. Terima kasih!';

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

<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class OpenAIService
{
    private $authorization;
    private $endpoint;
    private $httpClient;
    private $webToken;
    private $webClient;

    public function __construct()
    {
        $this->authorization = env('OPEN_AI_KEY');
        $this->webToken = env('OPEN_AI_SESSION');

        $this->endpoint = 'https://api.openai.com/v1/chat/completions';

        $this->httpClient = new Client([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->authorization,
            ],
        ]);

        $this->webClient = new Client([
            'headers' => [
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->webToken,
            ],
        ]);
    }

    public function checkCredit()
    {
        try {
            $response = $this->webClient->get('https://api.openai.com/dashboard/billing/credit_grants');

            if ($response->getStatusCode() === 200) {
                $creditData = json_decode($response->getBody(), true);
                return $creditData;
            } else {
                throw new \Exception('Error: Unexpected HTTP status code - ' . $response->getStatusCode());
            }
        } catch (RequestException $e) {
            $message = $e->getResponse() ? $e->getResponse()->getBody()->getContents() : $e->getMessage();
            $message = json_decode($message, true);
            throw new \Exception('Error checking the credit: ' . $message['error']['message']);
        }
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
                "model_pembelajaran": "(Berbentuk 1 Paragraf/Alinea)",
                "capaian_pembelajaran": "(Berbentuk 2 sampai 4 Paragraf/Alinea)"
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

    public function generateMaterialsPromptBetaContinue($existingData)
    {
        $prompt = 'Ini data hasil generate bahan ajar saya dalam bentuk JSON sebelumnya:';
        $prompt .= $existingData . ' ';
        $prompt .= 'Tolong cermati ya!';
        $prompt .= 'Analisis data dan berikan saya dalam bentuk JSON output dengan format :';
        $prompt .= '
        {
            "lembar_kerja_peserta_didik": {
                "type": "Ayo Analisis",
                "question": "" // Tipe  yang pertanyaana kritis dan panjang teks pertayannya untuk siswa analisis
            },
            "bahan_bacaan_guru_peserta_didik": [
                {
                    "title": "", // Judul terkait Materi
                    "content": "" // Isi Bahan terkait Materi dalam beberapa alinea
                },
                {
                    "title": "", // Judul terkait Materi
                    "content": "" // Isi Bahan terkait Materi dalam beberapa alinea
                },
                {
                    "title": "", // Judul terkait Materi
                    "content": "" // Isi Bahan terkait Materi dalam beberapa alinea
                }
            ]
        }
        ';
        $prompt .= '';

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

        Jelaskan jenjang sekolah, alokasi waktu, kompetensi inti (KI), kompetensi dasar, materi pembelajaran, dan kegiatan pembelajaran yang relevan.

        Rincikan kompetensi inti (KI) menjadi empat bagian yaitu KI-1 yang menyangkut spiritual, KI-2 yang menyangkut sosial, KI-3 yang menyangkut pengetahuan, dan KI-4 yang menyangkut keterampilan. Pastikan kompetensi inti (KI) memiliki informasi yang lengkap dan relevan.

        Selanjutnya susun 9 inti silabus yang berisikan kompetensi dasar, materi pembelajaran, dan kegiatan pembelajaran. Setiap kompetensi dasar memiliki 2 item, materi pembelajaran memiliki 4 item, dan kegiatan pembelajaran memiliki 4 item. Pastikan inti silabus beserta isinya memiliki informasi yang lengkap dan relevan.

        Pastikan setiap bagian memiliki informasi yang cukup dan relevan untuk membantu pendidik atau pembelajar memahami dan melaksanakan materi pembelajaran dengan efektif.

        Berikan saya output dengan format JSON seperti ini:

        {
            "informasi_umum": {
                "mata_pelajaran" : "",
                "nama_sekolah" : "",
                "jenjang_sekolah" : "",
                "tingkat_kelas": "",
                "nama_guru": "",
                "nip" : ""
            },
            "silabus_pembelajaran": {
                "mata_pelajaran" : "",
                "tingkat_kelas" : "",
                "alokasi_waktu" : "", Perhatian: Alokasi waktu berupa berapa jam pelajaran perminggunya
                "kompetensi_inti": [
                    "KI-1 (Spiritual) : ",
                    "KI-2 (Sosial) : ",
                    "KI-3 (Pengetahuan) : ",
                    "KI-4 (Keterampilan) : "
                ],
                "definisi_kompetensi_inti" : "", Perhatian: Definisi kompetensi inti adalah pencapaian atau tujuan dari keempat kompetensi inti tersebut (Berbentuk 1 Paragraf/Alinea)
                "inti_silabus" : [
                    {
                        "kompetensi_dasar" : ["", ""],
                        "materi_pembelajaran" : ["", "", "", ""],
                        "kegiatan_pembelajaran" : ["", "", "", ""]
                    },
                    {
                        "kompetensi_dasar" : ["", ""],
                        "materi_pembelajaran" : ["", "", "", ""],
                        "kegiatan_pembelajaran" : ["", "", "", ""]
                    },
                    {
                        "kompetensi_dasar" : ["", ""],
                        "materi_pembelajaran" : ["", "", "", ""],
                        "kegiatan_pembelajaran" : ["", "", "", ""]
                    },
                    {
                        "kompetensi_dasar" : ["", ""],
                        "materi_pembelajaran" : ["", "", "", ""],
                        "kegiatan_pembelajaran" : ["", "", "", ""]
                    },
                    {
                        "kompetensi_dasar" : ["", ""],
                        "materi_pembelajaran" : ["", "", "", ""],
                        "kegiatan_pembelajaran" : ["", "", "", ""]
                    },
                    {
                        "kompetensi_dasar" : ["", ""],
                        "materi_pembelajaran" : ["", "", "", ""],
                        "kegiatan_pembelajaran" : ["", "", "", ""]
                    },
                    {
                        "kompetensi_dasar" : ["", ""],
                        "materi_pembelajaran" : ["", "", "", ""],
                        "kegiatan_pembelajaran" : ["", "", "", ""]
                    },
                    {
                        "kompetensi_dasar" : ["", ""],
                        "materi_pembelajaran" : ["", "", "", ""],
                        "kegiatan_pembelajaran" : ["", "", "", ""]
                    },
                    {
                        "kompetensi_dasar" : ["", ""],
                        "materi_pembelajaran" : ["", "", "", ""],
                        "kegiatan_pembelajaran" : ["", "", "", ""]
                    },
                ], Perhatikan: Pastikan setiap bagian pada item "inti_silabus" memiliki jumlah informasi yang lengkap dan relevan
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
                        "instructions": "",
                        "kriteria_penilaian": ["", "", ""], //Paragraf membentuk kriteria penialaian jawaban
                    }
                ]
            }' . PHP_EOL .
            "Jumlah soal yang diminta: {$number_of_question}";

        // Return the generated prompt
        return $prompt;
    }

    public function generateExercisesChoicePrompt($subject, $grade, $number_of_question, $notes)
    {
        // Construct the prompt
        $prompt = "Generate latihan untuk mata pelajaran: {$subject}, tingkat kelas: {$grade} dengan memperhatikan catatan khusus berikut: {$notes}. Saya butuh dihasilkan {$number_of_question} soal . " . PHP_EOL .
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
                    }
                ]
            }' . PHP_EOL .
            "Mohon berikan jawaban yang jelas dan sesuai dengan pertanyaan. Silakan isi semua pertanyaan dan pilihan jawaban yang tersedia." . PHP_EOL .
            "Pastikan jumlah soal yang diminta sesuai dengan kebutuhan saya yaitu {$number_of_question} soal dan dapat diselesaikan dengan baik dalam waktu yang telah dialokasikan." . PHP_EOL .
            "Terima kasih atas kerja sama Anda.";

        // Return the generated prompt
        return $prompt;
    }

    public function generateHintsPrompt($subject, $grade, $addNotes)
    {
        $prompt = "Generate kisi-kisi untuk mata pelajaran: {$subject}, tingkat kelas: {$grade}, dengan memerhatikan catatan khusus berikut: {$addNotes}. " . PHP_EOL .
            "Perhatian: Mohon jawab dengan format JSON berikut:" . PHP_EOL .
            '{
                "informasi_umum": {
                    "penyusun": "",
                    "instansi": "",
                    "mata_pelajaran": "",
                    "tingkat_kelas": "",
                },
                "kisi_kisi": [
                    {
                        "capaian_pembelajaran": "", //Perhatian: Mohon berikan capaian pembelajaran sesuai dengan catatan khusus.
                        "domain/elemen": "", //Perhatian: Mohon berikan nama domain/elemen yang relevan dengan capaian pembelajaran.
                        "pokok_materi": "", //Perhatian: Mohon berikan pokok materi yang merupakan rangkuman dari domain/elemen berupa satu sampai dua kata.
                        "indikator_soal": ["", "", ""],
                    },
                                        {
                        "capaian_pembelajaran": "", //Perhatian: Mohon berikan capaian pembelajaran sesuai dengan catatan khusus.
                        "domain/elemen": "", //Perhatian: Mohon berikan nama domain/elemen yang relevan dengan capaian pembelajaran.
                        "pokok_materi": "", //Perhatian: Mohon berikan pokok materi yang merupakan rangkuman dari domain/elemen berupa satu sampai dua kata.
                        "indikator_soal": ["", "", ""],
                    },
                                        {
                        "capaian_pembelajaran": "", //Perhatian: Mohon berikan capaian pembelajaran sesuai dengan catatan khusus.
                        "domain/elemen": "", //Perhatian: Mohon berikan nama domain/elemen yang relevan dengan capaian pembelajaran.
                        "pokok_materi": "", //Perhatian: Mohon berikan pokok materi yang merupakan rangkuman dari domain/elemen berupa satu sampai dua kata.
                        "indikator_soal": ["", "", ""],
                    }
                ]
            }' . PHP_EOL .
            "Terima kasih atas kerja sama Anda.";

        return $prompt;
    }

    public function generateBahanAjarPrompt($subject, $grade, $addNotes)
    {
        $prompt = "Generate kisi-kisi untuk mata pelajaran: {$subject}, tingkat kelas: {$grade}, dengan memerhatikan catatan khusus berikut: {$addNotes}. " . PHP_EOL .
            "Perhatian: Mohon jawab dengan format JSON berikut:" . PHP_EOL .
            '{
                "informasi_umum": {
                    "penyusun": "",
                    "instansi": "",
                    "tingkat_kelas": "",
                    "mata_pelajaran": "",
                    "judul_materi": "", //Perhatian: Mohon berikan judul materi sesuai dengan catatan khusus.
                },
                "pendahuluan": {
                    "definisi": "(Berupa paragraf yang menjelaskan definisi dari judul materi)",
                },
                "konten":[
                    {
                        "nama_konten": "", //Perhatian: Nama konten adalah sub bab materi dari judul materi.
                        "isi_konten": "(Berupa paragraf yang menjelaskan materi)",
                    },
                    {
                        "nama_konten": "", //Perhatian: Nama konten adalah sub bab materi dari judul materi.
                        "isi_konten": "(Berupa paragraf yang menjelaskan materi)",
                    },
                    {
                        "nama_konten": "", //Perhatian: Nama konten adalah sub bab materi dari judul materi.
                        "isi_konten": "(Berupa paragraf yang menjelaskan materi)",
                    },
                    {
                        "nama_konten": "", //Perhatian: Nama konten adalah sub bab materi dari judul materi.
                        "isi_konten": "(Berupa paragraf yang menjelaskan materi)",
                    },
                    {
                        "nama_konten": "", //Perhatian: Nama konten adalah sub bab materi dari judul materi.
                        "isi_konten": "(Berupa paragraf yang menjelaskan materi)",
                    },
                ],
                "studi_kasus": [
                    {
                        "nama_studi_kasus": "", //Perhatian: Mohon berikan nama studi kasus yang relevan dengan catatan khusus.
                        "isi_studi_kasus": "(Berupa paragraf yang merupakan isi dari studi kasus)",
                    },
                    {
                        "nama_studi_kasus": "", //Perhatian: Mohon berikan nama studi kasus yang relevan dengan catatan khusus.
                        "isi_studi_kasus": "(Berupa paragraf yang merupakan isi dari studi kasus)",
                    },
                ],
                "quiz": {
                    "soal_quiz": "", //Perhatian: Mohon berikan soal quiz yang berkaitan dengan judul materi dan catatan khusus.
                },
                "evaluasi": {
                    "isi_evaluasi": "(Berupa paragraf yang merupakan evaluasi atau rangkuman dari materi)",
                }
                "lampiran":{
                    "sumber_referensi": ["", "", "", "", ""] //Perhatian: Mohon berikan 5 sumber referensi yang relevan dengan materi seperti mengutip dari jurnal ilmiah, artikel ilmiah, buku pelajaran, jangan berupa data fiktif! Pastikan sumber referensi menggunakan format referensi yang sesuai.
                }
            }' . PHP_EOL .
            "Terima kasih atas kerja sama Anda.";

        return $prompt;
    }
}

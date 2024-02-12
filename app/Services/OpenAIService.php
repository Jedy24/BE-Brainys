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
                "alokasi_waktu": "",
                "kompetensi_awal": "(Berbentuk 1 Paragraf/Alinea)",
                "profil_pelajar_pancasila": "(Berbentuk 1 Paragraf/Alinea)",
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
            },
            "pemahaman_bermakna": {
                "topik": "(Berbentuk 1 Paragraf/Alinea)"
            },
            "pertanyaan_pemantik": ["", "", "", ""],
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
                }
            ]
            "lampiran": {
                "glorasium_materi": ["", "", "", "", "", "", "", "", "", ""] //Berikan 10 item glorasium masing masing 1 item 1 kalimat penjelasan aftar alfabetis istilah dalam suatu ranah pengetahuan tertentu yang dilengkapi dengan definisi untuk istilah-istilah tersebut
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
}

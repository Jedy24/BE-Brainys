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

            Notes Tambahan: '.$addNotes.'

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
                            "kompetensi_dasar_name": "",
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
                            "kompetensi_dasar_name": "",
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
                            "kompetensi_dasar_name": "",
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
                            "kompetensi_dasar_name": "",
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

    public function sendMessage(string $message): string
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
}

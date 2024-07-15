<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;

class HintHistoryExport implements FromArray, WithHeadings, WithStyles, WithEvents
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        $result = [
            [
                'Satuan Pendidikan',
                'Mata Pelajaran',
                'Fase',
                'Jumlah Soal',
                'Penulis Soal',
                'Capaian Pembelajaran',
                'Domain/Elemen',
                'Pokok Materi',
                'Kisi Kisi'
            ]
        ];

        $data = $this->data;

        // Adding the general information
        $result[] = [
            $data['informasi_umum']['instansi'],
            $data['informasi_umum']['mata_pelajaran'],
            $data['informasi_umum']['kelas'],
            $data['informasi_umum']['jumlah_soal'],
            $data['informasi_umum']['penyusun'],
            $data['informasi_umum']['capaian_pembelajaran_redaksi'],
            $data['informasi_umum']['elemen_capaian'],
            $data['informasi_umum']['pokok_materi'],
            ''  // Placeholder for 'Kisi Kisi'
        ];

        // Adding the kisi kisi data
        foreach ($data['kisi_kisi'] as $kisi) {
            $result[] = [
                '', '', '', '', '', '',
                '', '',
                "No: {$kisi['nomor']} - Indikator: {$kisi['indikator_soal']} - No Soal: {$kisi['no_soal']}"
            ];
        }

        return $result;
    }

    public function headings(): array
    {
        return [
            'Satuan Pendidikan',
            'Mata Pelajaran',
            'Fase',
            'Jumlah Soal',
            'Penulis Soal',
            'Capaian Pembelajaran',
            'Domain/Elemen',
            'Pokok Materi',
            'Kisi Kisi'
        ];
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => ['font' => ['bold' => true]],
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();
                $sheet->getColumnDimension('A')->setWidth(20);
                $sheet->getColumnDimension('B')->setWidth(30);
                $sheet->getColumnDimension('C')->setWidth(15);
                $sheet->getColumnDimension('D')->setWidth(15);
                $sheet->getColumnDimension('E')->setWidth(25);
                $sheet->getColumnDimension('F')->setWidth(50);
                $sheet->getColumnDimension('G')->setWidth(20);
                $sheet->getColumnDimension('H')->setWidth(25);
                $sheet->getColumnDimension('I')->setWidth(50);
                $sheet->getStyle('A1:I1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            },
        ];
    }
}

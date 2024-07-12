<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;

class ATPExport implements FromArray, WithHeadings, WithStyles, WithEvents
{
    protected $data;

    public function __construct(array $data)
    {
        $this->data = $data;
    }

    public function array(): array
    {
        $result = [
            ['Fase', 'Mata Pelajaran', 'Elemen', 'Capaian Pembelajaran', 'Capaian Pembelajaran Redaksi', 'Pekan', 'Alur']
        ];

        $data = $this->data;

        $result[] = [
            $data['fase'],
            $data['mata_pelajaran'],
            $data['elemen'],
            $data['capaian_pembelajaran'],
            $data['capaian_pembelajaran_per_tahun'],
            $data['pekan'],
            ''  // Placeholder for 'Alur'
        ];

        foreach ($data['alur'] as $alur) {
            $result[] = [
                '', '', '', '', '', '',
                'No: ' . $alur['no'] . ' - ' . $alur['tujuan_pembelajaran'],
                'Kata/Frase Kunci: ' . implode(', ', $alur['kata_frase_kunci']),
                'Profil Pelajar Pancasila: ' . implode(', ', $alur['profil_pelajar_pancasila']),
                'Glosasium: ' . $alur['glorasium']
            ];
        }

        return $result;
    }

    public function headings(): array
    {
        return [
            'Fase',
            'Mata Pelajaran',
            'Elemen',
            'Capaian Pembelajaran',
            'Capaian Pembelajaran Redaksi',
            'Pekan',
            'Alur Details'
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
                $sheet->mergeCells('G1:I1');
                $sheet->getColumnDimension('A')->setWidth(15);
                $sheet->getColumnDimension('B')->setWidth(30);
                $sheet->getColumnDimension('C')->setWidth(20);
                $sheet->getColumnDimension('D')->setWidth(50);
                $sheet->getColumnDimension('E')->setWidth(50);
                $sheet->getColumnDimension('F')->setWidth(10);
                $sheet->getColumnDimension('G')->setWidth(20);
                $sheet->getColumnDimension('H')->setWidth(30);
                $sheet->getColumnDimension('I')->setWidth(30);
                $sheet->getRowDimension('1')->setRowHeight(30);
                $sheet->getStyle('A1:I1')->getAlignment()->setHorizontal(\PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER);
            },
        ];
    }
}

<?php

namespace App\Exports;

use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithEvents;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use Maatwebsite\Excel\Events\AfterSheet;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Style\Alignment;

class ATPExport implements FromArray, WithStyles, WithEvents
{
    protected $data;

    // Property untuk menyimpan jumlah baris yang dibutuhkan
    protected $totalRows;

    public function __construct(array $data)
    {
        $this->data = $data;
        $this->totalRows = count($data['alur']) + 10; // Jumlah alur + 10 baris header dan informasi lainnya
    }

    public function array(): array
    {
        $result = [];

        // Add information rows before starting from A6
        $result[] = ['ALUR TUJUAN PEMBELAJARAN'];
        $result[] = [$this->data['informasi_umum']['mata_pelajaran']];
        $result[] = [$this->data['informasi_umum']['kelas']];
        $result[] = ['Penulis: ' . $this->data['informasi_umum']['penyusun']];
        $result[] = ['', ''];

        // Main Data starting from row 6
        $result[] = ['CAPAIAN PEMBELAJARAN', $this->data['capaian_pembelajaran']];
        $result[] = ['CAPAIAN PEMBELAJARAN PER TAHUN', $this->data['capaian_pembelajaran_per_tahun']];
        $result[] = ['ELEMEN/DOMAIN', $this->data['elemen']];
        $result[] = ['PEKAN', $this->data['pekan'] . ' Pekan'];
        $result[] = ['PEKAN KE', 'TUJUAN PEMBELAJARAN', 'KATA/FRASE KUNCI', 'PROFIL PELAJAR PANCASILA', 'GLOSARIUM'];

        // Alur Data
        foreach ($this->data['alur'] as $alur) {
            $result[] = [
                $alur['no'],
                $alur['tujuan_pembelajaran'],
                implode(', ', $alur['kata_frase_kunci']),
                implode(', ', $alur['profil_pelajar_pancasila']),
                $alur['glosarium']
            ];
        }

        return $result;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            'A1:A4' => [
                'font' => ['bold' => true, 'size' => 14]
            ],
            'A6:A9' => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FCE5CD']]
            ],
            'A10:E10' => [
                'font' => ['bold' => true],
                'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['argb' => 'FCE5CD']]
            ]
        ];
    }

    public function registerEvents(): array
    {
        return [
            AfterSheet::class => function (AfterSheet $event) {
                $sheet = $event->sheet->getDelegate();

                // Set column widths
                $sheet->getColumnDimension('A')->setWidth(20);
                $sheet->getColumnDimension('B')->setWidth(55);
                $sheet->getColumnDimension('C')->setWidth(30);
                $sheet->getColumnDimension('D')->setWidth(30);
                $sheet->getColumnDimension('E')->setWidth(30);

                // Merge cells
                $sheet->mergeCells('A1:E1');
                $sheet->mergeCells('A2:E2');
                $sheet->mergeCells('A3:E3');
                $sheet->mergeCells('A4:E4');
                $sheet->mergeCells('B6:E6');
                $sheet->mergeCells('B7:E7');
                $sheet->mergeCells('B8:E8');
                $sheet->mergeCells('B9:E9');

                // Set text wrapping and auto-adjust row height for rows 6 to 11
                $sheet->getStyle('A6:B9')->getAlignment()->setWrapText(true);
                $sheet->getStyle('B11:E' . $this->totalRows)->getAlignment()->setWrapText(true);
                foreach (range(6, 11) as $row) {
                    $sheet->getRowDimension($row)->setRowHeight(-1);
                }

                // Set alignment
                $sheet->getStyle('A1:A4')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A1:A4')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                $sheet->getStyle('A6:B9')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle('A6:B9')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                $sheet->getStyle('A10:E10')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A10:E10')->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                $sheet->getStyle('A10:A' . $this->totalRows)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
                $sheet->getStyle('A10:A' . $this->totalRows)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);
                $sheet->getStyle('B11:E' . $this->totalRows)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_LEFT);
                $sheet->getStyle('B11:E' . $this->totalRows)->getAlignment()->setVertical(Alignment::VERTICAL_TOP);

                // Apply borders to the range dynamically based on total rows
                $styleArray = [
                    'borders' => [
                        'allBorders' => [
                            'borderStyle' => Border::BORDER_THIN,
                            'color' => ['argb' => 'FF000000'], // Black color
                        ],
                    ],
                ];
                $sheet->getStyle('A6:E' . $this->totalRows)->applyFromArray($styleArray);
            },
        ];
    }
}

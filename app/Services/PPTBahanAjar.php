<?php

namespace App\Services;

use PhpOffice\PhpPresentation\DocumentLayout;
use PhpOffice\PhpPresentation\PhpPresentation;
use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\Slide\Background\Image;
use PhpOffice\PhpPresentation\Style\Alignment;
use PhpOffice\PhpPresentation\Style\Color;
use PhpOffice\PhpPresentation\Style\Bullet;

class PPTBahanAjar
{
    public function createPresentation(array $data, string $outputPath)
    {
        //  dd($data);

        $presentation = new PhpPresentation();
        $presentation->getLayout()->setDocumentLayout(DocumentLayout::LAYOUT_SCREEN_16X9);

        // Layout
        $width = $presentation->getLayout()->getCX(DocumentLayout::UNIT_PIXEL);
        $height = $presentation->getLayout()->getCY(DocumentLayout::UNIT_PIXEL);

        // Upscale
        $scaleFactor = 2;
        $newWidth = $width * $scaleFactor;
        $newHeight = $height * $scaleFactor;

        // Menyesuaikan ukuran slide
        $this->setSlideSize($presentation, $newWidth, $newHeight);

        // Slide 1: Judul
        $titleWidth = $newWidth - 240;
        $textElementsSlide1 = [
            [
                'x' => 240,
                'y' => 340,
                'width' => $titleWidth,
                'height' => 88,
                'text' => $data['informasi_umum']['nama_bahan_ajar'],
                'fontSize' => 46,
                'fontName' => 'Arial',
                'isBold' => true,
                'horizontalAlign' => Alignment::HORIZONTAL_LEFT,
                'verticalAlign' => Alignment::VERTICAL_TOP,
                'color' => Color::COLOR_BLACK,
                'lineSpacing' => 150,
                'isBullet' => false
            ],
            [
                'x' => 240,
                'y' => 440,
                'width' => $titleWidth,
                'height' => 84,
                'text' => $data['informasi_umum']['penyusun'],
                'fontSize' => 24,
                'fontName' => 'Arial',
                'isBold' => true,
                'horizontalAlign' => Alignment::HORIZONTAL_LEFT,
                'verticalAlign' => Alignment::VERTICAL_TOP,
                'color' => Color::COLOR_BLACK,
                'lineSpacing' => 150,
                'isBullet' => false
            ],
            [
                'x' => 240,
                'y' => 530,
                'width' => $titleWidth,
                'height' => 84,
                'text' => $data['informasi_umum']['mata_pelajaran'] . ' | ' . $data['informasi_umum']['judul_materi'],
                'fontSize' => 20,
                'fontName' => 'Arial',
                'isBold' => true,
                'horizontalAlign' => Alignment::HORIZONTAL_LEFT,
                'verticalAlign' => Alignment::VERTICAL_TOP,
                'color' => Color::COLOR_BLACK,
                'lineSpacing' => 150,
                'isBullet' => false
            ],
            [
                'x' => 240,
                'y' => 590,
                'width' => $titleWidth,
                'height' => 84,
                'text' => $data['informasi_umum']['instansi'] . ' | Kelas ' . $data['informasi_umum']['tingkat_kelas'],
                'fontSize' => 20,
                'fontName' => 'Arial',
                'isBold' => false,
                'horizontalAlign' => Alignment::HORIZONTAL_LEFT,
                'verticalAlign' => Alignment::VERTICAL_TOP,
                'color' => Color::COLOR_BLACK,
                'lineSpacing' => 150,
                'isBullet' => false
            ]
        ];
        $this->createSlideWithText($presentation, 'Judul', public_path('ppt_template/Bahan_Ajar_Blank/1.jpg'), $textElementsSlide1);

        // Slide 2: Pendahuluan
        $titleWidth = $newWidth - 240;
        $textElementsSlide2 = [
            [
                'x' => 90,
                'y' => 120,
                'width' => $titleWidth,
                'height' => 88,
                'text' => 'Pendahuluan',
                'fontSize' => 44,
                'fontName' => 'Arial',
                'isBold' => true,
                'horizontalAlign' => Alignment::HORIZONTAL_JUSTIFY,
                'verticalAlign' => Alignment::VERTICAL_TOP,
                'color' => Color::COLOR_BLACK,
                'lineSpacing' => 150,
                'isBullet' => false
            ],
            [
                'x' => 90,
                'y' => 225,
                'width' => $titleWidth,
                'height' => 200,
                'text' => $data['pendahuluan']['definisi'],
                'fontSize' => 24,
                'fontName' => 'Arial',
                'isBold' => false,
                'horizontalAlign' => Alignment::HORIZONTAL_JUSTIFY,
                'verticalAlign' => Alignment::VERTICAL_TOP,
                'color' => Color::COLOR_BLACK,
                'lineSpacing' => 150,
                'isBullet' => false
            ],
        ];
        $this->createSlideWithText($presentation, 'Judul', public_path('ppt_template/Bahan_Ajar_Blank/2.jpg'), $textElementsSlide2);

        // Slide 3 - Konten
        foreach ($data['konten'] as $item) {
            $titleWidth = $newWidth - 240;
            $textElementsSlide3 = [
                [
                    'x' => 90,
                    'y' => 120,
                    'width' => $titleWidth,
                    'height' => 88,
                    'text' => $item['nama_konten'],
                    'fontSize' => 44,
                    'fontName' => 'Arial',
                    'isBold' => true,
                    'horizontalAlign' => Alignment::HORIZONTAL_JUSTIFY,
                    'verticalAlign' => Alignment::VERTICAL_TOP,
                    'color' => Color::COLOR_BLACK,
                    'lineSpacing' => 150,
                    'isBullet' => false
                ],
                [
                    'x' => 90,
                    'y' => 225,
                    'width' => $titleWidth,
                    'height' => 200,
                    'text' => $item['isi_konten'],
                    'fontSize' => 24,
                    'fontName' => 'Arial',
                    'isBold' => false,
                    'horizontalAlign' => Alignment::HORIZONTAL_JUSTIFY,
                    'verticalAlign' => Alignment::VERTICAL_TOP,
                    'color' => Color::COLOR_BLACK,
                    'lineSpacing' => 150,
                    'isBullet' => false
                ],
            ];
            $this->createSlideWithText($presentation, 'Judul', public_path('ppt_template/Bahan_Ajar_Blank/2.jpg'), $textElementsSlide3);
        }

        // Slide 4 - Studi Kasus
        foreach ($data['studi_kasus'] as $item) {
            $titleWidth = $newWidth - 520;
            $textElementsSlide4 = [
                [
                    'x' => 190,
                    'y' => 140,
                    'width' => $titleWidth,
                    'height' => 88,
                    'text' => 'Studi Kasus',
                    'fontSize' => 44,
                    'fontName' => 'Arial',
                    'isBold' => true,
                    'horizontalAlign' => Alignment::HORIZONTAL_JUSTIFY,
                    'verticalAlign' => Alignment::VERTICAL_TOP,
                    'color' => 'FF224BFD',
                    'lineSpacing' => 150,
                    'isBullet' => false
                ],
                [
                    'x' => 190,
                    'y' => 230,
                    'width' => $titleWidth,
                    'height' => 88,
                    'text' => $item['nama_studi_kasus'],
                    'fontSize' => 36,
                    'fontName' => 'Arial',
                    'isBold' => true,
                    'horizontalAlign' => Alignment::HORIZONTAL_JUSTIFY,
                    'verticalAlign' => Alignment::VERTICAL_TOP,
                    'color' => Color::COLOR_BLACK,
                    'lineSpacing' => 150,
                    'isBullet' => false
                ],
                [
                    'x' => 190,
                    'y' => 330,
                    'width' => $titleWidth,
                    'height' => 200,
                    'text' => $item['isi_studi_kasus'],
                    'fontSize' => 24,
                    'fontName' => 'Arial',
                    'isBold' => false,
                    'horizontalAlign' => Alignment::HORIZONTAL_JUSTIFY,
                    'verticalAlign' => Alignment::VERTICAL_TOP,
                    'color' => Color::COLOR_BLACK,
                    'lineSpacing' => 150,
                    'isBullet' => false
                ],
            ];
            $this->createSlideWithText($presentation, 'Judul', public_path('ppt_template/Bahan_Ajar_Blank/3.jpg'), $textElementsSlide4);
        }

        // Slide 5: Quiz Latihan
        $titleWidth = $newWidth - 750;
        $textElementsSlide5 = [
            [
                'x' => 570,
                'y' => 120,
                'width' => $titleWidth,
                'height' => 88,
                'text' => 'Latihan',
                'fontSize' => 44,
                'fontName' => 'Arial',
                'isBold' => true,
                'horizontalAlign' => Alignment::HORIZONTAL_JUSTIFY,
                'verticalAlign' => Alignment::VERTICAL_TOP,
                'color' => 'FF224BFD',
                'lineSpacing' => 150,
                'isBullet' => false
            ],
            [
                'x' => 570,
                'y' => 220,
                'width' => $titleWidth,
                'height' => 200,
                'text' => $data['quiz']['soal_quiz'],
                'fontSize' => 22,
                'fontName' => 'Arial',
                'isBold' => false,
                'horizontalAlign' => Alignment::HORIZONTAL_JUSTIFY,
                'verticalAlign' => Alignment::VERTICAL_TOP,
                'color' => Color::COLOR_BLACK,
                'lineSpacing' => 150,
                'isBullet' => false
            ],
        ];
        $this->createSlideWithText($presentation, 'Judul', public_path('ppt_template/Bahan_Ajar_Blank/4.jpg'), $textElementsSlide5);

        // Slide 6: Evaluasi/Rangkuman
        $titleWidth = $newWidth - 950;
        $textElementsSlide6 = [
            [
                'x' => 80,
                'y' => 120,
                'width' => $titleWidth,
                'height' => 88,
                'text' => 'Rangkuman/Evaluasi',
                'fontSize' => 44,
                'fontName' => 'Arial',
                'isBold' => true,
                'horizontalAlign' => Alignment::HORIZONTAL_JUSTIFY,
                'verticalAlign' => Alignment::VERTICAL_TOP,
                'color' => 'FF224BFD',
                'lineSpacing' => 150,
                'isBullet' => false
            ],
            [
                'x' => 80,
                'y' => 220,
                'width' => $titleWidth,
                'height' => 200,
                'text' => $data['evaluasi']['isi_evaluasi'],
                'fontSize' => 22,
                'fontName' => 'Arial',
                'isBold' => false,
                'horizontalAlign' => Alignment::HORIZONTAL_JUSTIFY,
                'verticalAlign' => Alignment::VERTICAL_TOP,
                'color' => Color::COLOR_BLACK,
                'lineSpacing' => 150,
                'isBullet' => false
            ],
        ];
        $this->createSlideWithText($presentation, 'Judul', public_path('ppt_template/Bahan_Ajar_Blank/5.jpg'), $textElementsSlide6);

        // Slide 7: Evaluasi/Rangkuman
        $references = "";
        foreach ($data['lampiran']['sumber_referensi'] as $element) {
            $references .= $element . "\n";
        }
        $references = rtrim($references, "\n");

        $titleWidth = $newWidth - 200;
        $textElementsSlide7 = [
            [
                'x' => 80,
                'y' => 120,
                'width' => $titleWidth,
                'height' => 88,
                'text' => 'Referensi',
                'fontSize' => 44,
                'fontName' => 'Arial',
                'isBold' => true,
                'horizontalAlign' => Alignment::HORIZONTAL_JUSTIFY,
                'verticalAlign' => Alignment::VERTICAL_TOP,
                'color' => Color::COLOR_BLACK,
                'lineSpacing' => 150,
                'isBullet' => false
            ],
            [
                'x' => 80,
                'y' => 220,
                'width' => $titleWidth,
                'height' => 450,
                'text' => $references,
                'fontSize' => 22,
                'fontName' => 'Arial',
                'isBold' => false,
                'horizontalAlign' => Alignment::HORIZONTAL_JUSTIFY,
                'verticalAlign' => Alignment::VERTICAL_TOP,
                'color' => Color::COLOR_BLACK,
                'lineSpacing' => 150,
                'isBullet' => true
            ],
        ];
        $this->createSlideWithText($presentation, 'Judul', public_path('ppt_template/Bahan_Ajar_Blank/6.jpg'), $textElementsSlide7);

        // Slide: Terima kasih
        $this->createSlideWithText($presentation, 'Terima Kasih', public_path('ppt_template/Bahan_Ajar_Blank/7.jpg'), []);

        // Menghapus slide pertama (index 0)
        $presentation->removeSlideByIndex(0);

        // Menyimpan presentasi sebagai file PPTX
        $oWriterPPTX = IOFactory::createWriter($presentation, 'PowerPoint2007');
        $oWriterPPTX->save($outputPath);

        return "Presentasi berhasil dibuat!";
    }

    private function setSlideSize($presentation, $width, $height)
    {
        $presentation->getLayout()->setCX($width, DocumentLayout::UNIT_PIXEL);
        $presentation->getLayout()->setCY($height, DocumentLayout::UNIT_PIXEL);
    }

    private function createTextShape($slide, $x, $y, $width, $height, $text, $fontSize, $fontName = 'Arial', $isBold = false, $horizontalAlign = Alignment::HORIZONTAL_LEFT, $verticalAlign = Alignment::VERTICAL_TOP, $color = Color::COLOR_BLACK, $lineSpacing = 100, $isBullet = false)
    {
        $shape = $slide->createRichTextShape()
            ->setHeight($height)
            ->setWidth($width)
            ->setOffsetX($x)
            ->setOffsetY($y);

        $paragraph = $shape->createParagraph();

        if ($isBullet) {
            $paragraph->getBulletStyle()
                ->setBulletType(Bullet::TYPE_BULLET)
                ->setBulletChar('-');
            $shape->getActiveParagraph()
                ->getAlignment()
                ->setMarginLeft(24)
                ->setIndent(-24);
        }

        $textRun = $paragraph->createTextRun($text);
        $textRun->getFont()
            ->setSize($fontSize)
            ->setName($fontName)
            ->setBold($isBold)
            ->setColor(new Color($color));

        $paragraph->getAlignment()
            ->setHorizontal($horizontalAlign)
            ->setVertical($verticalAlign);
        $paragraph->setLineSpacing($lineSpacing);

        return $shape;
    }

    private function setBackgroundImage($slide, $imagePath)
    {
        $backgroundImage = new Image();
        $backgroundImage->setPath($imagePath);
        $slide->setBackground($backgroundImage);
    }

    private function createSlideWithText($presentation, $slideName, $backgroundImagePath, $textElements)
    {
        $slide = $presentation->createSlide();
        $slide->setName($slideName);
        $this->setBackgroundImage($slide, $backgroundImagePath);

        foreach ($textElements as $element) {
            $this->createTextShape(
                $slide,
                $element['x'],
                $element['y'],
                $element['width'],
                $element['height'],
                $element['text'],
                $element['fontSize'],
                $element['fontName'],
                $element['isBold'],
                $element['horizontalAlign'],
                $element['verticalAlign'],
                $element['color'],
                $element['lineSpacing'],
                $element['isBullet']
            );
        }

        return $slide;
    }
}

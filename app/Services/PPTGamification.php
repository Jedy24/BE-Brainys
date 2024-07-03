<?php

namespace App\Services;

use PhpOffice\PhpPresentation\DocumentLayout;
use PhpOffice\PhpPresentation\PhpPresentation;
use PhpOffice\PhpPresentation\IOFactory;
use PhpOffice\PhpPresentation\Slide\Background\Image;
use PhpOffice\PhpPresentation\Style\Alignment;
use PhpOffice\PhpPresentation\Style\Color;
use PhpOffice\PhpPresentation\Style\Bullet;

class PPTGamification
{
    public function createPresentation(array $data, string $outputPath)
    {
        // dd($data);

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
                'height' => 84,
                'text' => $data['tema'],
                'fontSize' => 42,
                'fontName' => 'Arial',
                'isBold' => true,
                'horizontalAlign' => Alignment::HORIZONTAL_LEFT,
                'verticalAlign' => Alignment::VERTICAL_CENTER,
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
                'text' => $data['informasi_umum']['mata_pelajaran'] . ' | ' . $data['informasi_umum']['materi_pelajaran'],
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
                'text' => $data['informasi_umum']['instansi'] . ' | Kelas ' . $data['informasi_umum']['kelas'],
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
        $this->createSlideWithText($presentation, 'Judul', public_path('ppt_template/Gamifikasi_Blank/1.jpg'), $textElementsSlide1);

        // Slide 2: Elemen Gamifikasi
        $gamificationText = "";
        foreach ($data['elemen_gamifikasi'] as $element) {
            $gamificationText .= $element['judul'] . ": " . $element['deskripsi'] . "\n";
        }
        $gamificationText = rtrim($gamificationText, "\n");

        $textElementsSlide2 = [
            [
                'x' => 510,
                'y' => 35,
                'width' => $newWidth - 644,
                'height' => 84,
                'text' => 'Konsep Utama',
                'fontSize' => 40,
                'fontName' => 'Arial',
                'isBold' => true,
                'horizontalAlign' => Alignment::HORIZONTAL_LEFT,
                'verticalAlign' => Alignment::VERTICAL_TOP,
                'color' => 'FF224BFD',
                'lineSpacing' => 100,
                'isBullet' => false
            ],
            [
                'x' => 510,
                'y' => 105,
                'width' => $newWidth - 644,
                'height' => 150,
                'text' => $data['konsep_utama'],
                'fontSize' => 22,
                'fontName' => 'Arial',
                'isBold' => false,
                'horizontalAlign' => Alignment::HORIZONTAL_JUSTIFY,
                'verticalAlign' => Alignment::VERTICAL_TOP,
                'color' => Color::COLOR_BLACK,
                'lineSpacing' => 150,
                'isBullet' => false
            ],
            [
                'x' => 510,
                'y' => 280,
                'width' => $newWidth - 644,
                'height' => 84,
                'text' => 'Elemen Gamifikasi',
                'fontSize' => 40,
                'fontName' => 'Arial',
                'isBold' => true,
                'horizontalAlign' => Alignment::HORIZONTAL_LEFT,
                'verticalAlign' => Alignment::VERTICAL_TOP,
                'color' => 'FF224BFD',
                'lineSpacing' => 150,
                'isBullet' => false
            ],
            [
                'x' => 510,
                'y' => 375,
                'width' => $newWidth - 644,
                'height' => 84,
                'text' => $gamificationText,
                'fontSize' => 22,
                'fontName' => 'Arial',
                'isBold' => false,
                'horizontalAlign' => Alignment::HORIZONTAL_JUSTIFY,
                'verticalAlign' => Alignment::VERTICAL_TOP,
                'color' => Color::COLOR_BLACK,
                'lineSpacing' => 150,
                'isBullet' => true
            ]
        ];
        $this->createSlideWithText($presentation, 'Elemen Gamifikasi', public_path('ppt_template/Gamifikasi_Blank/2.jpg'), $textElementsSlide2);

        // Slide 3: Misi & Tantangan
        $missionText = "";
        foreach ($data['misi_dan_tantangan'] as $item) {
            if ($item['jenis'] == 'Misi') {
                $missionText .= "" . $item['deskripsi'] . " (" . $item['poin'] . " poin)\n";
            }
        }
        $missionText = rtrim($missionText, "\n");

        $challengeText = "";
        foreach ($data['misi_dan_tantangan'] as $item) {
            if ($item['jenis'] == 'Tantangan') {
                $challengeText .= "" . $item['deskripsi'] . " (" . $item['poin'] . " poin)\n";
            }
        }
        $challengeText = rtrim($challengeText, "\n");

        $textElementsSlide3 = [
            [
                'x' => 70,
                'y' => 120,
                'width' => 1200,
                'height' => 80,
                'text' => 'Misi Gamifikasi',
                'fontSize' => 40,
                'fontName' => 'Arial',
                'isBold' => true,
                'horizontalAlign' => Alignment::HORIZONTAL_LEFT,
                'verticalAlign' => Alignment::VERTICAL_CENTER,
                'color' => 'FF224BFD',
                'lineSpacing' => 150,
                'isBullet' => false
            ],
            [
                'x' => 70,
                'y' => 190,
                'width' => 900,
                'height' => 750,
                'text' => $missionText,
                'fontSize' => 20,
                'fontName' => 'Arial',
                'isBold' => false,
                'horizontalAlign' => Alignment::HORIZONTAL_JUSTIFY,
                'verticalAlign' => Alignment::VERTICAL_TOP,
                'color' => Color::COLOR_BLACK,
                'lineSpacing' => 150,
                'isBullet' => true
            ],
            [
                'x' => 70,
                'y' => 530,
                'width' => 1200,
                'height' => 80,
                'text' => 'Tantangan Gamifikasi',
                'fontSize' => 40,
                'fontName' => 'Arial',
                'isBold' => true,
                'horizontalAlign' => Alignment::HORIZONTAL_LEFT,
                'verticalAlign' => Alignment::VERTICAL_CENTER,
                'color' => 'FF224BFD',
                'lineSpacing' => 150,
                'isBullet' => false
            ],
            [
                'x' => 70,
                'y' => 600,
                'width' => 900,
                'height' => 750,
                'text' => $challengeText,
                'fontSize' => 20,
                'fontName' => 'Arial',
                'isBold' => false,
                'horizontalAlign' => Alignment::HORIZONTAL_JUSTIFY,
                'verticalAlign' => Alignment::VERTICAL_TOP,
                'color' => Color::COLOR_BLACK,
                'lineSpacing' => 150,
                'isBullet' => true
            ],
        ];
        $this->createSlideWithText($presentation, 'Misi dan Tantangan', public_path('ppt_template/Gamifikasi_Blank/3.jpg'), $textElementsSlide3);

        // Slide 5
        foreach ($data['langkah_implementasi'] as $langkah) {
            $deskripsiLangkah = "";
            foreach ($langkah['deskripsi'] as $item) {
                $deskripsiLangkah .= $item . "\n";
            }
            $deskripsiLangkah = rtrim($deskripsiLangkah, "\n");

            $textElementsSlide4 = [
                [
                    'x' => 70,
                    'y' => 140,
                    'width' => $newWidth - 300,
                    'height' => 84,
                    'text' => 'Langkah ' . $langkah['langkah'] . ' : ' . $langkah['judul'],
                    'fontSize' => 36,
                    'fontName' => 'Arial',
                    'isBold' => true,
                    'horizontalAlign' => Alignment::HORIZONTAL_LEFT,
                    'verticalAlign' => Alignment::VERTICAL_TOP,
                    'color' => 'FF000000',
                    'lineSpacing' => 150,
                    'isBullet' => false
                ],
                [
                    'x' => 70,
                    'y' => 250,
                    'width' => $newWidth - 500,
                    'height' => 84,
                    'text' => $deskripsiLangkah,
                    'fontSize' => 24,
                    'fontName' => 'Arial',
                    'isBold' => false,
                    'horizontalAlign' => Alignment::HORIZONTAL_JUSTIFY,
                    'verticalAlign' => Alignment::VERTICAL_TOP,
                    'color' => 'FF000000',
                    'lineSpacing' => 150,
                    'isBullet' => true
                ],
            ];
            $this->createSlideWithText($presentation, 'Langkah Implementasi', public_path('ppt_template/Gamifikasi_Blank/4.jpg'), $textElementsSlide4);
        }

        // Slide: Terima kasih
        $this->createSlideWithText($presentation, 'Terima Kasih', public_path('ppt_template/Gamifikasi_Blank/5.jpg'), []);

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

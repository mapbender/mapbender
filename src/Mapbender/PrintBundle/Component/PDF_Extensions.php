<?php

namespace Mapbender\PrintBundle\Component;

use setasign\Fpdi\Fpdi;

class PDF_Extensions extends Fpdi
{
    public function __construct($orientation = 'P', $unit = 'mm', $size = 'A4',
                                protected ?string $resourceDir = null,
                                protected ?array $customFonts = null
    )
    {
        parent::__construct($orientation, $unit, $size);

        if (!is_array($customFonts)) return;
        foreach($customFonts as $fontFamily => $configuration) {
            foreach($configuration as $fontStyle => $fileName) {
                $fontStyleFpdf = match(strtolower((string) $fontStyle)) {
                    'bold' => 'B',
                    'italic' => 'I',
                    'bolditalic', 'italicbold' => 'BI',
                    default => ''
                };
                $fontFile = "$fileName.json";
                if (!file_exists("$resourceDir/fonts/$fontFile")) {
                    // fpdf fonts can alternatively be declared in php files
                    $fontFile = "$fileName.php";
                }
                if (!file_exists("$resourceDir/fonts/$fontFile")) {
                    throw new \RuntimeException("Could not locate configured font file $fileName (font family $fontFamily). Check custom-fonts.md in documentation.");
                }
                $this->AddFont($fontFamily, $fontStyleFpdf, $fontFile, "$resourceDir/fonts");
            }
        }
    }

    /**
     * @return mixed
     */
    public function getHeight()
    {
        return $this->h;
    }

    /**
     * @return mixed
     */
    public function getWidth()
    {
        return $this->w;
    }


    function TextWithDirection($x, $y, $txt, $direction = 'R'): void
    {
        if ($direction == 'R')
            $s = sprintf('BT %.2F %.2F %.2F %.2F %.2F %.2F Tm (%s) Tj ET', 1, 0, 0, 1, $x * $this->k, ($this->h - $y) * $this->k, $this->_escape($txt));
        elseif ($direction == 'L')
            $s = sprintf('BT %.2F %.2F %.2F %.2F %.2F %.2F Tm (%s) Tj ET', -1, 0, 0, -1, $x * $this->k, ($this->h - $y) * $this->k, $this->_escape($txt));
        elseif ($direction == 'U')
            $s = sprintf('BT %.2F %.2F %.2F %.2F %.2F %.2F Tm (%s) Tj ET', 0, 1, -1, 0, $x * $this->k, ($this->h - $y) * $this->k, $this->_escape($txt));
        elseif ($direction == 'D')
            $s = sprintf('BT %.2F %.2F %.2F %.2F %.2F %.2F Tm (%s) Tj ET', 0, -1, 1, 0, $x * $this->k, ($this->h - $y) * $this->k, $this->_escape($txt));
        else
            $s = sprintf('BT %.2F %.2F Td (%s) Tj ET', $x * $this->k, ($this->h - $y) * $this->k, $this->_escape($txt));
        if ($this->ColorFlag)
            $s = 'q ' . $this->TextColor . ' ' . $s . ' Q';
        $this->_out($s);
    }

    /**
     * Returns number of lines required for rendering given $text in a MultiCell
     * with given $width.
     */
    public function getMultiCellTextHeight(string $text, int|float $width): int
    {
        /** @var static|\FPDF $tempPdf */
        $tempPdf = new static(resourceDir: $this->resourceDir, customFonts: $this->customFonts);
        $tempPdf->AddPage();
        $tempPdf->SetXY(0, 0);
        // clone font attributes
        $tempPdf->SetFont($this->FontFamily, $this->FontStyle, $this->FontSizePt);
        $tempPdf->MultiCell($width, 1, $text);
        return $tempPdf->GetY();
    }

    public function GetStringWidth($s): float
    {
        return parent::GetStringWidth($s) + 2*$this->cMargin;
    }
}

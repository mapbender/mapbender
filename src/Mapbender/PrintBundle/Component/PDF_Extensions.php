<?php

namespace Mapbender\PrintBundle\Component;

class PDF_Extensions extends \FPDI
{

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

    
    function TextWithDirection($x, $y, $txt, $direction='R')
    {
        if ($direction=='R')
            $s=sprintf('BT %.2F %.2F %.2F %.2F %.2F %.2F Tm (%s) Tj ET', 1, 0, 0, 1, $x*$this->k, ($this->h-$y)*$this->k, $this->_escape($txt));
        elseif ($direction=='L')
            $s=sprintf('BT %.2F %.2F %.2F %.2F %.2F %.2F Tm (%s) Tj ET', -1, 0, 0, -1, $x*$this->k, ($this->h-$y)*$this->k, $this->_escape($txt));
        elseif ($direction=='U')
            $s=sprintf('BT %.2F %.2F %.2F %.2F %.2F %.2F Tm (%s) Tj ET', 0, 1, -1, 0, $x*$this->k, ($this->h-$y)*$this->k, $this->_escape($txt));
        elseif ($direction=='D')
            $s=sprintf('BT %.2F %.2F %.2F %.2F %.2F %.2F Tm (%s) Tj ET', 0, -1, 1, 0, $x*$this->k, ($this->h-$y)*$this->k, $this->_escape($txt));
        else
            $s=sprintf('BT %.2F %.2F Td (%s) Tj ET', $x*$this->k, ($this->h-$y)*$this->k, $this->_escape($txt));
        if ($this->ColorFlag)
            $s='q '.$this->TextColor.' '.$s.' Q';
        $this->_out($s);
    }

    /**
     * Returns number of lines required for rendering given $text in a MultiCell
     * with given $width.
     *
     * @param string $text
     * @param float $width
     * @return int
     */
    public function getMultiCellTextHeight($text, $width)
    {
        /** @var static|\FPDF $tempPdf */
        $tempPdf = new static();
        $tempPdf->AddPage();
        $tempPdf->SetXY(0, 0);
        // clone font attributes
        $tempPdf->SetFont($this->FontFamily, $this->FontStyle, $this->FontSizePt);
        $tempPdf->MultiCell($width, 1, $text);
        return $tempPdf->GetY();
    }
}

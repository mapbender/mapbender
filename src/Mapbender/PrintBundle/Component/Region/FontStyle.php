<?php


namespace Mapbender\PrintBundle\Component\Region;


use Mapbender\PrintBundle\Component\OdgParser;

/**
 * Font style for template regions. Initialized by OdgParser and assigned to 'fields'-style regions only,
 * at least currently.
 */
class FontStyle
{
    /** @var string */
    protected $fontName;
    /** @var string */
    protected $color;
    /** @var float */
    protected $size;

    /**
     * @param string $fontName
     * @param float $size (silently dropping trailing 'pt'...)
     * @param string $color CSS hex style ('#ff00ff')
     */
    public function __construct($fontName, $size, $color)
    {
        if (!$fontName) {
            throw new \InvalidArgumentException("Font name empty " . print_r($fontName, true));
        }
        $this->fontName = $fontName;

        if (!preg_match('/^#?[0-9a-f]{3}([0-9a-f]{3}?)$/i', $color)) {
            throw new \InvalidArgumentException("Invalid font color, expected CSS hex style, got " . print_r($color, true));
        }
        $this->color = $color;
        if ($size <= 0) {
            throw new \InvalidArgumentException("Invalid font size " . print_r($size, true));
        }
        $this->size = floatval($size);
    }

    /**
     * @return string
     */
    public function getFontName()
    {
        return $this->fontName;
    }

    /**
     * @return float
     */
    public function getSize()
    {
        return $this->size;
    }

    /**
     * @return string hex coded (w/ leading hash)
     */
    public function getColor()
    {
        return $this->color;
    }

    /**
     * Gets an appropriate (minimum) line height in mm, for use in MultiCell rendering.
     * @see \FPDF::MultiCell()
     * @return float
     */
    public function getLineHeightMm()
    {
        // Font size is in 'pt'. Convert pt to mm for line height.
        // see https://en.wikipedia.org/wiki/Point_(typography)
        return .353 * $this->getSize();
    }

    /**
     * Returns new instance set up with OdgParser defaults.
     *
     * @return FontStyle
     */
    public static function defaultFactory()
    {
        return new static(OdgParser::DEFAULT_FONT_NAME, OdgParser::DEFAULT_FONT_SIZE, OdgParser::DEFAULT_FONT_COLOR);
    }
}

<?php
namespace Mapbender\PrintBundle\Component;

use Mapbender\PrintBundle\Component\Region\FontStyle;
use Symfony\Component\Config\Definition\Exception\Exception;

/**
 * Extract named region dimensions and font styles from an ODG document.
 *
 * Registered at mapbender.print.template_parser.service
 */
class OdgParser
{
    /** Default orientation */
    const DEFAULT_ORIENTATION = 'landscape';

    /** Default font name */
    const DEFAULT_FONT_NAME = 'Arial';

    /** Default font color */
    const DEFAULT_FONT_COLOR = '#000000';

    /** Default font size */
    const DEFAULT_FONT_SIZE = '10pt';

    /** Conversion factor for meters to centimeters */
    const CONVERSION_FACTOR = 10;

    /** @var string */
    protected $sourcePath;

    /**
     * @param string $sourcePath
     */
    public function __construct($sourcePath)
    {
        $this->sourcePath = rtrim($sourcePath, '/');
    }


    /**
     * Reads zipped ODG file and return content as string
     *
     * @param $template
     * @param $file
     * @return string
     */
    private function readOdgFile($template, $file)
    {
        $odgFile = "{$this->sourcePath}/{$template}.odg";
        $xml          = null;

        if(!is_file($odgFile)){
            throw new Exception("Print template '$template' doesn't exists.");
        }

        $open = zip_open($odgFile);
        while ($zip_entry = zip_read($open)) {
            if (zip_entry_name($zip_entry) == $file) {
                zip_entry_open($open, $zip_entry);
                $xml = zip_entry_read($zip_entry, 204800);
                break;
            }
        }
        zip_close($open);
        return $xml;
    }

    /**
     * Get map geometry size as JSON object
     *
     * @param $template
     * @return string JSON object {width: n, height: n}
     */
    public function getMapSize($template)
    {
        $doc        = new \DOMDocument();
        $xmlContent = $this->readOdgFile($template, 'content.xml');
        $doc->loadXML($xmlContent);

        /** @var \DOMElement $draMapNode */
        $draMapNode = (new \DOMXPath($doc))->query("//draw:custom-shape[@draw:name='map']")->item(0);

        return json_encode(array(
            'width'  => static::parseNumericNodeAttribute($draMapNode, 'svg:width') / static::CONVERSION_FACTOR,
            'height' => static::parseNumericNodeAttribute($draMapNode, 'svg:height') / static::CONVERSION_FACTOR
        ));
    }

    /**
     * Get print configuration
     *
     * @param string $templateName
     * @return Template
     */
    public function getConf($templateName)
    {
        /** @var \DOMElement $pageGeometry */
        /** @var \DOMElement $customShape */
        /** @var \DOMElement $textNode */
        /** @var \DOMElement $textParagraph */
        /** @var \DOMElement $styleNode */

        $doc = new \DOMDocument();
        $doc->loadXML($this->readOdgFile($templateName, 'styles.xml'));
        $xPath        = new \DOMXPath($doc);
        $node         = $xPath->query("//style:page-layout-properties");
        $pageGeometry = $node->item(0);
        $data         = array(
            'orientation' => static::parseNodeAttribute($pageGeometry, 'style:print-orientation', static::DEFAULT_ORIENTATION),
            'pageSize'    => array(
                'height' => static::parseNumericNodeAttribute($pageGeometry, 'fo:page-height'),
                'width'  => static::parseNumericNodeAttribute($pageGeometry, 'fo:page-width'),
            ),
        );
        $templateObject = new Template($data['pageSize']['width'], $data['pageSize']['height'], $data['orientation']);

        $doc = new \DOMDocument();
        $doc->loadXML($this->readOdgFile($templateName, 'content.xml'));

        $xPath        = new \DOMXPath($doc);
        $customShapes = $xPath->query("//draw:custom-shape");
        foreach ($customShapes as $customShape) {
            $shapeData = static::parseShape($customShape);
            $shapeName = $customShape->getAttribute('draw:name');
            $templateRegion = new TemplateRegion($shapeData['width'], $shapeData['height'], array(
                $shapeData['x'],
                $shapeData['y'],
            ));
            $templateObject->addRegion($shapeName, $templateRegion);
            // @todo: extract font styles for all shapes?
        }

        foreach ($xPath->query("draw:page/draw:frame", $doc->getElementsByTagName('drawing')->item(0)) as $node) {
            $name      = $node->getAttribute('draw:name');
            $style     = null;

            if (empty($name)) {
                continue;
            }
            $fieldShapeData = static::parseShape($node);
            $textField = new TemplateRegion($fieldShapeData['width'], $fieldShapeData['height'], array(
                $fieldShapeData['x'],
                $fieldShapeData['y'],
            ));

            // Recognize font name and size
            $textParagraph = $xPath->query("draw:text-box/text:p", $node)->item(0);
            $textNode      = $xPath->query("draw:text-box/text:p/text:span", $node)->item(0);
            if ($textNode) {
                $style = $textNode->getAttribute('text:style-name');
            } elseif ($textParagraph) {
                $style = $textParagraph->getAttribute('text:style-name');
            }

            if ($style) {
                $styleNode = $xPath->query('//style:style[@style:name="' . $style . '"]/style:text-properties')->item(0);
                $fontSize  = static::parseNodeAttribute($styleNode, 'fo:font-size', static::DEFAULT_FONT_SIZE);
                $fontColor = static::parseNodeAttribute($styleNode, 'fo:color', static::DEFAULT_FONT_COLOR);
            } else {
                $fontSize = static::DEFAULT_FONT_SIZE;
                $fontColor = static::DEFAULT_FONT_COLOR;
            }
            $textField->setFontStyle(new FontStyle(static::DEFAULT_FONT_NAME, $fontSize, $fontColor));
            $templateObject->addTextField($name, $textField);
        }
        return $templateObject;
    }

    /**
     * Parse node attribute
     *
     * @param \DOMElement $node
     * @param  string     $xPath
     * @param mixed       $defaultValue
     * @return mixed
     */
    static function parseNodeAttribute($node, $xPath, $defaultValue = '')
    {
        $value = $node->getAttribute($xPath);
        return empty($value) ? $defaultValue : $value;
    }

    /**
     * Parse float node attribute
     *
     * @param \DOMElement $node
     * @param  string     $xPath
     * @param mixed       $defaultValue
     * @return mixed
     */
    static function parseNumericNodeAttribute($node, $xPath, $defaultValue = 0)
    {
        $value = $node->getAttribute($xPath);
        if (!empty($value) && is_string($value) && strlen($value) > 2) {
            $value = substr($value, 0, -2) * static::CONVERSION_FACTOR;
        } else {
            $value = $defaultValue;
        }
        return $value;
    }

    /**
     * Parse shape parameters
     *
     * @param $customShape
     * @return array
     */
    public static function parseShape($customShape)
    {
        return array(
            'width'  => static::parseNumericNodeAttribute($customShape, 'svg:width'),
            'height' => static::parseNumericNodeAttribute($customShape, 'svg:height'),
            'x'      => static::parseNumericNodeAttribute($customShape, 'svg:x'),
            'y'      => static::parseNumericNodeAttribute($customShape, 'svg:y'),
        );
    }
}
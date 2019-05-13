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
     * @param string $templateName
     * @param string $extension
     * @return string
     */
    public function getTemplateFilePath($templateName, $extension)
    {
        $extension = ltrim($extension, '.');
        return "{$this->sourcePath}/{$templateName}.{$extension}";
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
        $odgPath = $this->getTemplateFilePath($template, 'odg');
        $xml = null;

        if(!is_file($odgPath)){
            throw new Exception("Print template '$template' doesn't exists.");
        }

        $open = zip_open($odgPath);
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
        $templateObject = $this->parsePageGeometryIntoObject($node->item(0));

        $doc = new \DOMDocument();
        $doc->loadXML($this->readOdgFile($templateName, 'content.xml'));

        $xPath        = new \DOMXPath($doc);
        $customShapes = $xPath->query("//draw:custom-shape");
        foreach ($customShapes as $customShape) {
            $shapeName = $customShape->getAttribute('draw:name');
            if (!$shapeName) {
                continue;
            }
            $templateRegion = $this->parseShapeIntoRegion($customShape);
            $templateRegion->setName($shapeName);
            // @todo: extract (non-default) font styles for all shapes?
            $templateRegion->setFontStyle(FontStyle::defaultFactory());
            $templateObject->addRegion($templateRegion);
        }

        foreach ($xPath->query("draw:page/draw:frame", $doc->getElementsByTagName('drawing')->item(0)) as $node) {
            $name      = $node->getAttribute('draw:name');

            if (empty($name)) {
                continue;
            }
            $textField = $this->parseShapeIntoRegion($node);
            $styleNode = $this->detectStyleNode($xPath, $node);
            $styleData = $this->parseStyleNode($styleNode);
            $textField->setFontStyle(new FontStyle($styleData['font'], $styleData['fontsize'], $styleData['fontcolor']));
            $textField->setName($name);
            $templateObject->addTextField($textField);
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
     * @param \DOMElement $customShape
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

    /**
     * @param \DOMElement $customShape
     * @return TemplateRegion
     */
    public function parseShapeIntoRegion($customShape)
    {
        $shd = static::parseShape($customShape);
        return new TemplateRegion($shd['width'], $shd['height'], array($shd['x'],$shd['y']));

    }

    /**
     * @param \DOMElement $node
     * @return array
     */
    public function parsePageGeometry($node)
    {
        return array(
            'orientation' => static::parseNodeAttribute($node, 'style:print-orientation', static::DEFAULT_ORIENTATION),
            'pageSize'    => array(
                'height' => static::parseNumericNodeAttribute($node, 'fo:page-height'),
                'width'  => static::parseNumericNodeAttribute($node, 'fo:page-width'),
            ),
        );
    }

    /**
     * @param \DOMElement $node
     * @return Template
     */
    public function parsePageGeometryIntoObject($node)
    {
        $data = $this->parsePageGeometry($node);
        return new Template($data['pageSize']['width'], $data['pageSize']['height'], $data['orientation']);
    }

    /**
     * @param \DOMXPath $xPath
     * @param \DOMElement $parentNode
     * @return \DOMElement|null
     */
    public function detectStyleNode($xPath, $parentNode)
    {
        $textParagraph = $xPath->query("draw:text-box/text:p", $parentNode)->item(0);
        $textNode      = $xPath->query("draw:text-box/text:p/text:span", $parentNode)->item(0);
        if ($textNode) {
            $styleAttribute = $textNode->getAttribute('text:style-name');
        } elseif ($textParagraph) {
            $styleAttribute = $textParagraph->getAttribute('text:style-name');
        } else {
            $styleAttribute = null;
        }
        if ($styleAttribute) {
            $selector = '//style:style[@style:name="' . $styleAttribute . '"]/style:text-properties';
            return $xPath->query($selector)->item(0);
        } else {
            return null;
        }
    }

    /**
     * Should extract font style properties from given $node, or default styles if node is emptyish.
     *
     * @param \DOMElement|null $node
     * @return array
     */
    public function parseStyleNode($node)
    {
        if ($node) {
            $fontSize  = static::parseNodeAttribute($node, 'fo:font-size', static::DEFAULT_FONT_SIZE);
            $fontColor = static::parseNodeAttribute($node, 'fo:color', static::DEFAULT_FONT_COLOR);
        } else {
            $fontSize = static::DEFAULT_FONT_SIZE;
            $fontColor = static::DEFAULT_FONT_COLOR;
        }
        return array(
            'font' => static::DEFAULT_FONT_NAME,
            'fontsize' => $fontSize,
            'fontcolor' => $fontColor,
        );
    }
}

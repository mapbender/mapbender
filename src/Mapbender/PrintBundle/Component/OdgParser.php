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
    const DEFAULT_ORIENTATION = 'landscape';
    const DEFAULT_FONT_NAME = 'Arial';
    const DEFAULT_FONT_COLOR = '#000000';
    const DEFAULT_ALIGNMENT = 'L';

    const DEFAULT_FONT_SIZE = '10pt';

    const STYLE_ATTRIBUTES = [
        'fontsize' => 'fo:font-size',
        'fontcolor' => 'fo:color',
        'alignment' => 'fo:text-align'
    ];

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
        $xml = '';

        if (!is_file($odgPath)) {
            throw new Exception("Print template '$template' doesn't exists.");
        }
        $zip = new \ZipArchive();
        $zip->open($odgPath, 16/** =\ZipArchive::RDONLY PHP >=7.4.3 */);
        $index = 0;
        while ($stat = $zip->statIndex($index)) {
            if ($stat['name'] === $file) {
                $handle = $zip->getStream($stat['name']);
                while (!\feof($handle)) {
                    $xml .= fread($handle, 1 << 20);
                }
                fclose($handle);
                break;
            }
            ++$index;
        }
        $zip->close();
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
        $doc = new \DOMDocument();
        $xmlContent = $this->readOdgFile($template, 'content.xml');
        $doc->loadXML($xmlContent);

        /** @var \DOMElement $draMapNode */
        $draMapNode = (new \DOMXPath($doc))->query("//draw:custom-shape[@draw:name='map']")->item(0);

        return json_encode(array(
            'width' => static::parseNumericNodeAttribute($draMapNode, 'svg:width') / static::CONVERSION_FACTOR,
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
        $xPath = new \DOMXPath($doc);
        $node = $xPath->query("//style:page-layout-properties");
        $templateObject = $this->parsePageGeometryIntoObject($node->item(0));

        $doc = new \DOMDocument();
        $doc->loadXML($this->readOdgFile($templateName, 'content.xml'));

        $xPath = new \DOMXPath($doc);
        $this->parseCustomShapes($templateObject, $xPath);
        $this->parseTextFields($templateObject, $xPath);
        return $templateObject;
    }

    protected function parseCustomShapes(Template $templateObject, \DOMXPath $xPath)
    {
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
    }

    protected function parseTextFields(Template $templateObject, \DOMXPath $xPath)
    {
        foreach ($xPath->query("draw:page/draw:frame", $xPath->document->getElementsByTagName('drawing')->item(0)) as $node) {
            $name = $node->getAttribute('draw:name');

            if (empty($name)) {
                continue;
            }
            $textField = $this->parseShapeIntoRegion($node);
            $styleNodes = $this->detectStyleNodes($xPath, $node);
            $styleData = $this->parseStyleNodes($styleNodes);
            $textField->setFontStyle(new FontStyle($styleData['font'], $styleData['fontsize'], $styleData['fontcolor']));
            $textField->setAlignment($styleData['alignment']);
            $textField->setName($name);
            $templateObject->addTextField($textField);
        }
        return $templateObject;
    }

    /**
     * Parse node attribute
     *
     * @param \DOMElement $node
     * @param string $xPath
     * @param mixed $defaultValue
     * @return mixed
     */
    static function parseNodeAttribute($node, $xPath, $defaultValue = '')
    {
        if ($node === null) return $defaultValue;
        $value = $node->getAttribute($xPath);
        return empty($value) ? $defaultValue : $value;
    }

    /**
     * Parse float node attribute
     *
     * @param \DOMElement $node
     * @param string $xPath
     * @param mixed $defaultValue
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
            'width' => static::parseNumericNodeAttribute($customShape, 'svg:width'),
            'height' => static::parseNumericNodeAttribute($customShape, 'svg:height'),
            'x' => static::parseNumericNodeAttribute($customShape, 'svg:x'),
            'y' => static::parseNumericNodeAttribute($customShape, 'svg:y'),
        );
    }

    /**
     * @param \DOMElement $customShape
     * @return TemplateRegion
     */
    public function parseShapeIntoRegion($customShape)
    {
        $shd = static::parseShape($customShape);
        return new TemplateRegion($shd['width'], $shd['height'], array($shd['x'], $shd['y']));

    }

    /**
     * @param \DOMElement $node
     * @return array
     */
    public function parsePageGeometry($node)
    {
        return array(
            'orientation' => static::parseNodeAttribute($node, 'style:print-orientation', static::DEFAULT_ORIENTATION),
            'pageSize' => array(
                'height' => static::parseNumericNodeAttribute($node, 'fo:page-height'),
                'width' => static::parseNumericNodeAttribute($node, 'fo:page-width'),
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
     * @return \DOMElement[]
     */
    public function detectStyleNodes(\DOMXPath $xPath, \DOMElement $parentNode): array
    {
        $nodes = [];
        foreach (["draw:text-box/text:p", "draw:text-box/text:p/text:span"] as $xPathString) {
            /** @var ?\DOMElement|null $textParagraph */
            $node = $xPath->query($xPathString, $parentNode)->item(0);
            if (!$node) continue;

            $styleAttribute = $node->getAttribute('text:style-name');
            if (!$styleAttribute) continue;

            foreach (['style:paragraph-properties', 'style:text-properties'] as $stylePropertyName) {
                $selector = '//style:style[@style:name="' . $styleAttribute . '"]/' . $stylePropertyName;
                $styleNode = $xPath->query($selector)->item(0);
                if ($styleNode !== null) $nodes[] = $styleNode;
            }
        }
        return $nodes;
    }

    /**
     * Should extract font style properties from given $node, or default styles if node is emptyish.
     *
     * @param \DOMElement[] $node
     * @return array
     */
    public function parseStyleNodes(array $nodes): array
    {
        $data = [
            'font' => static::DEFAULT_FONT_NAME,
            'fontsize' => static::DEFAULT_FONT_SIZE,
            'fontcolor' => static::DEFAULT_FONT_COLOR,
            'alignment' => static::DEFAULT_ALIGNMENT,
        ];

        foreach ($nodes as $node) {
            foreach (self::STYLE_ATTRIBUTES as $attribute => $xmlTag) {
                $result = static::parseNodeAttribute($node, $xmlTag, null);
                if ($result) $data[$attribute] = $result;
            }
        }
        return $data;
    }
}

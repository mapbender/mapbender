<?php
namespace Mapbender\PrintBundle\Component;

use Symfony\Component\Config\Definition\Exception\Exception;

class OdgParser
{

    /**
     * OdgParser constructor.
     *
     * @param $container
     */
    public function __construct($container)
    {
        $this->container = $container;
    }

    /**
     * @param $template
     * @param $file
     * @return string
     */
    private function readOdgFile($template, $file)
    {
        $resource_dir = $this->container->getParameter('kernel.root_dir') . '/Resources/MapbenderPrintBundle';
        $odgfile = $resource_dir . '/templates/' . $template . '.odg';

        if(!is_file($odgfile)){
            throw new Exception("Print template '$template' doesn't exists.");
        }

        $open = zip_open($odgfile);
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
     * @param $template
     * @return string
     */
    public function getMapSize($template)
    {
        $xml = $this->readOdgFile($template, 'content.xml');
        $doc = new \DOMDocument();
        $doc->loadXML($xml);
        $xpath = new \DOMXPath($doc);

        $node = $xpath->query("//draw:custom-shape[@draw:name='map']");
        $width = $node->item(0)->getAttribute('svg:width');
        $height = $node->item(0)->getAttribute('svg:height');

        $size = array();
        $size['width'] = substr($width, 0, -2);
        $size['height'] = substr($height, 0, -2);

        return json_encode($size);
    }

    /**
     * Get print configuration
     *
     * @param $template
     * @return array
     */
    public function getConf($template)
    {
        $data = array();

        //orientation
        $stylexml = $this->readOdgFile($template, 'styles.xml');
        $doc = new \DOMDocument();
        $doc->loadXML($stylexml);
        $xpath = new \DOMXPath($doc);
        $node = $xpath->query("//style:page-layout-properties");
        $data['orientation'] = $node->item(0)->getAttribute('style:print-orientation');
        $data['pageSize']['height'] = substr($node->item(0)->getAttribute('fo:page-height'), 0, -2) * 10;
        $data['pageSize']['width'] = substr($node->item(0)->getAttribute('fo:page-width'), 0, -2) * 10;

        $contentxml = $this->readOdgFile($template, 'content.xml');
        $doc = new \DOMDocument();
        $doc->loadXML($contentxml);
        $xpath = new \DOMXPath($doc);


        //$node = $xpath->query("//draw:custom-shape[@draw:name='map']");
        $imagenodes = $xpath->query("//draw:custom-shape");

        foreach ($imagenodes as $node) {
            $name = $node->getAttribute('draw:name');
            $width = $node->getAttribute('svg:width');
            $height = $node->getAttribute('svg:height');
            $x = $node->getAttribute('svg:x');
            $y = $node->getAttribute('svg:y');

            $data[$name]['width'] = substr($width, 0, -2) * 10;
            $data[$name]['height'] = substr($height, 0, -2) * 10;
            $data[$name]['x'] = substr($x, 0, -2) * 10;
            $data[$name]['y'] = substr($y, 0, -2) * 10;
        }

        $contextnode = $doc->getElementsByTagName('drawing')->item(0);
        $textnodes = $xpath->query("draw:page/draw:frame", $contextnode);
        foreach ($textnodes as $node) {
            $name = $node->getAttribute('draw:name');
            if ($name == '') {
                continue;
            }
            $width  = $node->getAttribute('svg:width');
            $height = $node->getAttribute('svg:height');
            $x      = $node->getAttribute('svg:x');
            $y      = $node->getAttribute('svg:y');
            $field  = array(
                'font'     => 'Arial',
                'width'    => substr($width, 0, -2) * 10,
                'height'   => substr($height, 0, -2) * 10,
                'x'        => substr($x, 0, -2) * 10,
                'y'        => substr($y, 0, -2) * 10,
            );

            // Recognize font name and size
            $textParagraph = $xpath->query("draw:text-box/text:p", $node)->item(0);
            $textNode      = $xpath->query("draw:text-box/text:p/text:span", $node)->item(0);
            if ($textNode) {
                $style = $textNode->getAttribute('text:style-name');
            } elseif ($textParagraph) {
                $style = $textParagraph->getAttribute('text:style-name');
            }
            if ($style) {
                $styleNode = $xpath->query('//style:style[@style:name="' . $style . '"]/style:text-properties')->item(0);
                $fontsize = $styleNode->getAttribute('fo:font-size');
                $color = $styleNode->getAttribute('fo:color');
            }
            $field['fontsize'] = $fontsize != '' ? $fontsize : '10pt';
            $field['color'] = $color != '' ? $color : '#000000';
            $data['fields'][ $name ] = $field;
        }
        return $data;
    }
}
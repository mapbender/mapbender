<?php


namespace Mapbender\CoreBundle\Component;


use Mapbender\Component\IconPackageInterface;
use Mapbender\Utils\HtmlUtil;

/**
 * Glyph icons from mapbender/mapbender-icons package
 * @see https://github.com/mapbender/icons
 */
class IconPackageMbIcons implements IconPackageInterface
{
    public function getChoices()
    {
        return array(
            'Layer tree' => 'icon-layer-tree',
            'Feature Info' => 'icon-feature-info',
            'Area ruler' => 'icon-area-ruler',
            'Polygon' => 'icon-polygon',
            'Line ruler' => 'icon-line-ruler',
            'Image Export' => 'icon-image-export',
            'Legend' => 'icon-legend',
            'About' => 'icon-about',
        );
    }

    public function getStyleSheets()
    {
        return array(
            'components/mapbender-icons/style.css',
        );
    }

    public function getIconMarkup($iconCode)
    {
        return HtmlUtil::renderTag('span', '', array(
            'class' => 'mb-glyphicon ' . $iconCode,
        ));
    }

    public function isHandled($iconCode)
    {
        return \in_array($iconCode, $this->getChoices());
    }
    
    public function getAliases()
    {
        return array();
    }
}

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
    protected bool $showDefaultIcons = true;

    public function __construct(bool $disableDefaultIcons)
    {
        $this->showDefaultIcons = !$disableDefaultIcons;
    }

    public function getChoices(): array
    {
        if (!$this->showDefaultIcons) return [];
        return [
            'mb.core.icon.mb.about' => 'icon-about',
            'mb.core.icon.mb.layer_tree' => 'icon-layer-tree',
            'mb.core.icon.mb.feature_info' => 'icon-feature-info',
            'mb.core.icon.mb.area_ruler' => 'icon-area-ruler',
            'mb.core.icon.mb.polygon' => 'icon-polygon',
            'mb.core.icon.mb.line_ruler' => 'icon-line-ruler',
            'mb.core.icon.mb.image_export' => 'icon-image-export',
            'mb.core.icon.mb.legend' => 'icon-legend',
        ];
    }

    public function getStyleSheets(): array
    {
        return ['components/mapbender-icons/style.css'];
    }

    public function getIconMarkup($iconCode, $additionalClass = '')
    {
        return HtmlUtil::renderTag('span', '', array(
            'class' => 'mb-glyphicon ' . $iconCode . ' ' . $additionalClass
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

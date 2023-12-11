<?php


namespace Mapbender\CoreBundle\Component;


use Mapbender\Component\IconPackageInterface;
use Mapbender\Utils\HtmlUtil;

class IconPackageFa4 implements IconPackageInterface
{
    protected bool $showDefaultIcons = true;
    protected array $additionalIcons = [];

    public function __construct(bool $disableDefaultIcons, ?array $additionalIcons)
    {
        $this->showDefaultIcons = !$disableDefaultIcons;
        if (is_array($additionalIcons)) $this->additionalIcons = $additionalIcons;
    }


    public function getStyleSheets()
    {
        return ['components/font-awesome/css/all.css'];
    }

    public function getChoices(bool $showAll = false)
    {
        $choices = [];
        if ($this->showDefaultIcons || $showAll) $choices = [
            /** @todo: localize labels */
            /** @todo: remove technocratic "(FontAwesome)" label postfixes */
            'About (FontAwesome)' => 'iconAbout',
            'Area ruler (FontAwesome)' => 'iconAreaRuler',
            'Feature info (FontAwesome)' => 'iconInfoActive',
            'GPS (FontAwesome)' => 'iconGps',
            'Home (FontAwesome)' => 'iconHome',
            'Legend (FontAwesome)' => 'iconLegend',
            'Print (FontAwesome)' => 'iconPrint',
            'Search (FontAwesome)' => 'iconSearch',
            'Layer tree (FontAwesome)' => 'iconLayertree',
            'Logout (FontAwesome)' => 'iconLogout',
            'WMS (FontAwesome)' => 'iconWms',
            'Help (FontAwesome)' => 'iconHelp',
            'Edit (FontAwesome)' => 'iconEdit',     // = formerly iconWmcEditor, iconSketch
            'WMC Loader (FontAwesome)' => 'iconWmcLoader',
            'Coordinates (FontAwesome)' => 'iconCoordinates',
            'Gps Target (FontAwesome)' => 'iconGpsTarget',
            'POI (FontAwesome)' => 'iconPoi',
            'Image Export (FontAwesome)' => 'iconImageExport',
            'Copyright (FontAwesome)' => 'iconCopyright',
            'Share (FontAwesome)' => 'iconShare',
            'Share Arrow(FontAwesome)' => 'iconShareArrow',
            'Refresh' => 'iconRefresh',
            'Earth (FontAwesome)' => 'iconEarth',
            'Map (FontAwesome)' => 'iconMap',
            'Map Pin (FontAwesome)' => 'iconMapPin',
        ];

        foreach ($this->additionalIcons as $icon) {
            $choices[$icon['title']] = $icon['name'];
        }

        return $choices;
    }

    public function getIconMarkup($iconCode)
    {
        $class = null;
        foreach ($this->additionalIcons as $icon) {
            if ($icon['name'] === $iconCode) {
                $class = $icon['class'];
            }
        }

        if (!$class) {
        switch ($iconCode) {
            default:
                throw new \LogicException("Unhandled icon code " . \var_export($iconCode, true));
            case 'iconAbout':
                $class = 'fas fa-users'; break;
            case 'iconAreaRuler':
                $class = 'fas fa-crop'; break;
            case 'iconInfoActive':
                $class = 'fas fa-circle-info'; break;
            case 'iconGps':
                $class = 'fas fa-location-dot'; break;
            case 'iconHome':
                $class = 'fas fa-house-chimney'; break;
            case 'iconLegend':
                $class = 'fas fa-th-list'; break;
            case 'iconLogout':
                $class = 'fas fa-right-from-bracket'; break;
            case 'iconPrint':
                $class = 'fas fa-print'; break;
            case 'iconSearch':
                $class = 'fas fa-magnifying-glass'; break;
            case 'iconLayertree':
                $class = 'fas fa-sitemap'; break;
            case 'iconWms':
                $class = 'fas fa-globe'; break;
            case 'iconHelp':
                $class = 'fas fa-circle-question'; break;
            case 'iconEdit':
            case 'iconWmcEditor':
            case 'iconSketch':
                $class = 'fas fa-pen-to-square'; break;
            case 'iconWmcLoader':
                $class = 'fas fa-folder-open'; break;
            case 'iconCoordinates':
            case 'iconGpsTarget':
                $class = 'fas fa-crosshairs'; break;
            case 'iconPoi':
                $class = 'fas fa-thumbtack'; break;
            case 'iconImageExport':
                $class = 'fas fa-camera'; break;
            case 'iconCopyright':
                $class = 'fas fa-copyright'; break;
            case 'iconShare':
                $class = 'fas fa-share-nodes'; break;
            case 'iconShareArrow':
                $class = 'fas fa-share'; break;
            case 'iconRefresh':
                $class = 'fas fa-rotate'; break;
            case 'iconMap':
                $class = 'far fa-map'; break;
            case 'iconMapPin':
                $class = 'fas fa-map-pin'; break;
            case 'iconEarth':
                $class = 'fas fa-earth-africa'; break;

        }
        }

        return HtmlUtil::renderTag('i', '', array(
            'class' => $class,
        ));
    }

    public function isHandled($iconCode)
    {
        foreach ($this->additionalIcons as $icon) {
            if ($icon['title'] === $iconCode) {
                return true;
            }
        }

        return \in_array($iconCode, $this->getChoices(true)) || \array_key_exists($iconCode, $this->getAliases());
    }

    public function getAliases()
    {
        return array(
            'iconWmcEditor' => 'iconEdit',
            'iconSketch' => 'iconEdit',
            'iconGpsTarget' => 'iconCoordinates',
            'iconReset' => 'iconRefresh',
        );
    }
}

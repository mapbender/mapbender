<?php


namespace Mapbender\CoreBundle\Component;


use Mapbender\Component\IconPackageInterface;
use Mapbender\Utils\HtmlUtil;

class IconPackageFa4 implements IconPackageInterface
{
    public function getStyleSheets()
    {
        return array(
            'components/font-awesome/css/all.css',
        );
    }

    public function getChoices()
    {
        return array(
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
            'Share' => 'iconShare',
            'Refresh' => 'iconRefresh',
        );
    }

    public function getIconMarkup($iconCode)
    {
        switch ($iconCode) {
            default:
                throw new \LogicException("Unhandled icon code " . \var_export($iconCode, true));
            case 'iconAbout':
                $class = 'fas fa-users'; break;
            case 'iconAreaRuler':
                $class = 'fas fa-crop'; break;
            case 'iconInfoActive':
                $class = 'fas fa-info-circle'; break;
            case 'iconGps':
                /** @todo FA5: confirm result is fa-map-marker-alt glyph (poked hole) */
                $class = 'fas fa-location-dot'; break;
            case 'iconHome':
                $class = 'fas fa-house'; break;
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
                /** @todo FA5: prefer fa-globe-americas..? (same result as FA4 fa-globe) */
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
                $class = 'fas fa-share'; break;
            case 'iconRefresh':
                $class = 'fas fa-rotate'; break;
        }
        return HtmlUtil::renderTag('i', '', array(
            'class' => $class,
        ));
    }

    public function isHandled($iconCode)
    {
        return \in_array($iconCode, $this->getChoices()) || \array_key_exists($iconCode, $this->getAliases());
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
